<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';
require_once 'src/services/secretary.php';

use App\Form\RegistrationType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MembershipController extends \Controller
{
	protected $view_name = 'membership';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelMember');

		parent::__construct($request, $router);
	}

	private function _send_confirmation_mail($confirmation_code)
	{
		$db = get_db();

		$data_str = $db->query_value(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s' AND confirmed_on IS NULL", $db->escape_string($confirmation_code)));

		if ($data_str === null)
			throw new \NotFoundException('Could not find registration code');

		$data = array_merge(json_decode($data_str, true), [
			'link' => $this->generate_url('join', ['confirmation_code' => $confirmation_code], UrlGeneratorInterface::ABSOLUTE_URL),
		]);

		$email = parse_email_object('join_confirm_membership.txt', $data);
		$email->send($data['email_address']);
	}

	protected function _process_confirm_mail($confirmation_code)
	{
		$db = get_db();

		$row = $db->query_first(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s' AND confirmed_on IS NULL",
			$db->escape_string($confirmation_code)));

		if (!$row)
			throw new \NotFoundException('Could not find registration code');

		$data = json_decode($row['data'], true);

		$data['confirmation_code'] = $confirmation_code;
		$data['name'] = $data['first_name'] . (strlen($data['family_name_preposition']) ? ' ' . $data['family_name_preposition'] : '') . ' ' . $data['family_name'];

		$email = parse_email_object('join_administratie.txt', $data);
		$email->send('administratie@svcover.nl');
	}

	protected function _process_confirm_secretary($confirmation_code)
	{
		$db = get_db();

		$row = $db->query_first(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s'",
			$db->escape_string($confirmation_code)));

		if (!$row)
			throw new \NotFoundException('Could not find registration code');

		$data = json_decode($row['data'], true);

		$response = get_secretary()->createPerson($data);

		$db->delete('registrations', sprintf("confirmation_code = '%s'", $db->escape_string($confirmation_code)));
	}

	public function run_registration()
	{
		$defaults = [
			'membership_study_phase' => 'b',
			'membership_year_of_enrollment' => time() < mktime(0, 0, 0, 7, 1, date('Y')) ? date('Y') - 1 : date('Y'),
		];
		$form = $this->createForm(RegistrationType::class, $defaults, ['mapped' => false]);
		$form->handleRequest($this->get_request());


		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();

			// Test whether email is already used
			// (already a member? Or previous member?)
			try {
				$existing_member = get_model('DataModelMember')->get_from_email($data['email_address']);
				return $this->view->render('known_member.twig', compact('existing_member'));
			} catch (\DataIterNotFoundException $e) {
				// All clear :)
			}

			/* Mailing is opt-out. We can do this, because assocations are allowed to contact their members 
			about activities without consent, as long as it is non-commercial. See this link (at nieuwsbrief)
			https://www.declercq.com/kennisblog/wat-betekent-de-avg-voor-verenigingen/
			For now, secretary subscribes members to this one, so send option_mailing to secretary…
			*/
			// TODO: Make mailing officially opt-out, with migration to explicitly opt-out everyone who didn't opt-in first…
			$data['option_mailing'] = true;

			// Same as email confirmation / password reset
			$confirmation_code = randstr(40);

			// Store this info temporarily in the database and send a confirmation mail
			$db = get_db();
			$db->insert('registrations', [
				'confirmation_code' => $confirmation_code,
				'data' => json_encode($data),
			]);
			$this->_send_confirmation_mail($confirmation_code);

			return $this->view->redirect($this->generate_url('join', ['submitted' => 'true']));
		}


		return $this->view->render('form.twig', [
			'terms' => get_model('DataModelEditable')->get_iter_from_title('Voorwaarden aanmelden'),
			'form' => $form->createView(),
		]);
	}

	protected function run_confirm($confirmation_code)
	{
		try {
			// First, send a mail to administratie@svcover.nl for archiving purposes
			$this->_process_confirm_mail($confirmation_code);
			
			// If that worked out right, we can mark this registration as confirmed.
			get_db()->update('registrations',
				['confirmed_on' => date('Y-m-d H:i:s')],
				sprintf("confirmation_code = '%s'", get_db()->escape_string($confirmation_code)));

			try {
				// Try to add the member to Secretary. If this works out correctly
				// the registration will be deleted (and Secretary will add the
				// member to the leden table through the API).
				$this->_process_confirm_secretary($confirmation_code);
			} catch (\Exception|\Error $e) {
				// Well, that didn't work out. Report the error to everybody.
				// The registration will be marked as confirmed, but not deleted
				// so one can try again later when Secretary is available again.
				sentry_report_exception($e);

				mail('webcie@svcover.nl',
					'Error during membership application',
					"Something went wrong while trying to add a new member to Secretary.\n" .
					"In case it helps, the confirmation code was: " . $confirmation_code . "\n" .
					"Maybe the website error log for " . date('Y-m-d H:i:s') . " will provide more insight. Or this:\n\n"
					. $e->getMessage() . "\n"
					. $e->getTraceAsString(),
					implode("\r\n", ['Content-Type: text/plain; charset=UTF-8']));

				mail('secretaris@svcover.nl',
					'Error during membership application (member not added to Secretary)',
					"Something went wrong while trying to add a new member to Secretary. The AC/DCee has been informed about this.\n" .
					"You can find the application in the administratie@svcover.nl mailbox.\n" .
					"In case it helps, the confirmation code was: " . $confirmation_code,
					implode("\r\n", ['Content-Type: text/plain; charset=UTF-8']));
			}

			return $this->view->redirect($this->generate_url('join', ['confirmed' => 'true']));
		} catch (\NotFoundException $e) {
			return $this->view->render('not_found.twig');
		}
	}

	public function run_pending_index()
	{
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR) &&
			!get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) &&
			!get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new \UnauthorizedException();

		$db = get_db();
		$registrations_query = "
			SELECT
				confirmation_code,
				data,
				registerd_on as registered_on,
				confirmed_on
			FROM
				registrations
			ORDER BY
				registerd_on DESC
		";
		$registrations = $db->query($registrations_query);

		$form = $this->createFormBuilder()
			->add('registration', ChoiceType::class, [
				'expanded' => true,
				'multiple' => true,
				'choices' => array_map(fn($r) => (object) $r, $registrations),
				'choice_label' => function ($entity) {
					return $entity->confirmation_code ?? '';
				},
				'choice_value' => function ($entity) {
					return $entity->confirmation_code ?? '';
				},
			])
			->add('push_to_secretary', SubmitType::class)
			->add('resend_confirmation', SubmitType::class)
			->add('delete', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		$message = null;
		if ($form->isSubmitted() && $form->isValid()) {
			if ($form->get('push_to_secretary')->isClicked()) {
				$success = 0;
				foreach ($form->get('registration')->getData() as $registration) {
					try {
						$this->_process_confirm_secretary($registration->confirmation_code);
						$success++;
					} catch (\Exception $e) {
						sentry_report_exception($e);
					}
				}
				$message = sprintf(
					'Added %d out of %d registrations to Secretary',
					$success,
					count($form->get('registration')->getData())
				);
			} elseif ($form->get('resend_confirmation')->isClicked()) {
				foreach ($form->get('registration')->getData() as $registration)
					$this->_send_confirmation_mail($registration->confirmation_code);
				$message = sprintf(
					'Resent %d confirmation emails',
					count($form->get('registration')->getData())
				);
			} elseif ($form->get('delete')->isClicked() && count($form->get('registration')->getData()) > 0) {
				$ids = array_map(fn($r) => $r->confirmation_code, $form->get('registration')->getData());
				$rows = $db->execute(sprintf(
					"DELETE FROM registrations WHERE confirmation_code IN (%s)",
					$db->quote_value($ids)
				));
				$message = sprintf('Deleted %d registrations', $rows);
			}
			// Query registrations again. Things might have changed.
			$registrations = $db->query($registrations_query);
		}

		foreach ($registrations as &$registration)
			$registration['data'] = json_decode($registration['data'], true);

		return $this->view->render('pending.twig', [
			'registrations' => $registrations,
			'message' => $message,
			'form' => $form->createView(),
		]);
	}

	protected function run_pending_update($confirmation_code)
	{
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR) &&
			!get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) &&
			!get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new \UnauthorizedException();

		// Load data
		$db = get_db();
		$row = $db->query_first(sprintf("SELECT * FROM registrations WHERE confirmation_code = '%s'",
			$db->escape_string($confirmation_code)));
		if ($row === null)
			throw new \NotFoundException();
		$row['data'] = json_decode($row['data'], true);

		// Create form
		$form = $this->createForm(RegistrationType::class, $row['data'], ['mapped' => false]);
		$form->handleRequest($this->get_request());

		// Handle form
		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$db->update('registrations',
				['data' => json_encode($data)],
				sprintf('confirmation_code = %s', $db->quote($confirmation_code))
			);
			return $this->view->redirect($this->generate_url('join', ['view' => 'pending-confirmation']));
		}

		return $this->view->render('pending_form.twig', [
			'registration' => $row,
			'form' => $form->createView(),
		]);
	}
	
	protected function run_impl()
	{
		if (isset($_GET['submitted']))
			return $this->view->render('submitted.twig');
		elseif (isset($_GET['confirmed']))
			return $this->view->render('confirmed.twig');
		elseif (!isset($_GET['view']) && !empty($_GET['confirmation_code']))
			return $this->run_confirm($_GET['confirmation_code']);
		elseif (isset($_GET['view']) && $_GET['view'] == 'pending-confirmation' && !empty($_GET['confirmation_code']))
			return $this->run_pending_update($_GET['confirmation_code']);
		elseif (isset($_GET['view']) && $_GET['view'] == 'pending-confirmation')
			return $this->run_pending_index();
		else
			return $this->run_registration();
	}
}
