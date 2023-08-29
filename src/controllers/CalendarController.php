<?php
namespace App\Controller;

require_once 'src/framework/member.php';
require_once 'src/framework/login.php';
require_once 'src/framework/form.php';
require_once 'src/framework/webcal.php';
require_once 'src/framework/markup.php';
require_once 'src/framework/controllers/ControllerCRUDForm.php';

use App\Form\EventType;
use App\Form\DataTransformer\IntToBooleanTransformer;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class CalendarController extends \ControllerCRUDForm
{
	protected $_var_id = 'agenda_id';

	protected $view_name = 'calendar';

	protected $form_type = EventType::class;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelAgenda');

		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
		{
			$parameters[$this->_var_id] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		return $this->generate_url('calendar', $parameters);
	}

	protected function _process_create(\DataIter $iter, FormInterface $form)
	{
		if (!\get_policy($iter)->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to create events!');

		// Some things break without end date (tot), so set end date to start date (van)
		if (empty($iter['tot']))
			$iter['tot'] = $iter['van'];

		$id = $this->model->propose_insert($iter);

		$iter->set_id($id);

		$_SESSION['alert'] = __('The new event is now waiting for approval. Once the board has accepted the event, it will be published on the website.');

		$placeholders = [
			'commissie_naam' => get_model('DataModelCommissie')->get_naam($iter['committee_id']),
			'member_naam' => member_full_name(get_identity()->member(), IGNORE_PRIVACY)
		];

		mail(
			get_config_value('defer_email_to', get_config_value('email_bestuur')),
			'New event ' . $iter['kop'],
			parse_email('agenda_add.txt', array_merge($iter->data, $placeholders, ['id' => $id])),
			"From: Study Association Cover <noreply@svcover.nl>\r\n"
		);

		return true;
	}

	public function run_update(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_update($iter))
			throw new \UnauthorizedException('You are not allowed to edit this ' . get_class($iter) . '.');

		$orig = \DataIterAgenda::from_iter($iter);

		$success = false;

		$form = $this->get_form($iter);

		if ($form->isSubmitted() && $form->isValid()) {
			// We could set $skip_confirmation in one statement, but I find this more readable
			$skip_confirmation = false;

			// If you update the facebook-id, description, image or location, no need to reconfirm.
			if (!array_diff(array_keys($iter->get_updated_fields($orig)), ['facebook_id', 'beschrijving', 'image_url', 'locatie']))
				$skip_confirmation = true;

			// Unless the event was in the past, then we need confirmation as we most likely shouldn't be changing things anyway
			if ((empty($orig['tot_datetime']) && $orig['van_datetime'] < new \DateTime()) || $orig['tot_datetime'] < new \DateTime())
				$skip_confirmation = false;

			// Previous exists and there is no need to let the board confirm it
			if ($skip_confirmation)
			{
				$this->model->update($iter);

				$_SESSION['alert'] = __("The changes you've made to this event have been published.");
			}

			// Previous item exists but it needs to be confirmed first.
			else
			{
				$override_id = $this->model->propose_update($iter);

				$_SESSION['alert'] = __('The changes to the event are waiting for approval. Once the board has accepted the changes, they will be published on the website.');

				$placeholders = [
					'commissie_naam' => get_model('DataModelCommissie')->get_naam($iter['committee_id']),
					'member_naam' => member_full_name(get_identity()->member(), IGNORE_PRIVACY)
				];

				mail(
					get_config_value('defer_email_to', get_config_value('email_bestuur')),
					'Updated event ' . $iter['kop'] . ($iter->get('kop') != $orig->get('kop') ? ' was ' . $orig->get('kop') : ''),
					parse_email('agenda_mod.txt', array_merge($iter->data, $placeholders, ['id' => $override_id])),
					"From: Study Association Cover <noreply@svcover.nl>\r\n"
				);
			}

			$success = true;
		}

		return $this->view()->render_update($iter, $form, $success);
	}

	protected function _index()
	{
		$selected_year = isset($_GET['year']) ? intval($_GET['year']) : null;

		// No screwing around with invalid dates anymore
		if ($selected_year < 1993 || $selected_year > date('Y') + 2)
			$selected_year = null;

		if ($selected_year === null)
			return $this->model->get_agendapunten();
		
		$from = sprintf('%d-09-01', $selected_year);
		$till = sprintf('%d-08-31', $selected_year + 1);

		$punten = $this->model->get($from, $till, true);

		return $punten;
	}
	
	public function run_moderate(\DataIterAgenda $iter = null)
	{
		$events = array_filter($this->model->get_proposed(), [get_policy($this->model), 'user_can_moderate']);
		return $this->view->render('moderate.twig', [
			'iters' => $events,
			'highlighted_id' => $iter ? $iter['id'] : null,
		]);
	}
	
	public function run_moderate_accept(\DataIterAgenda $iter)
	{
		if (!get_policy($this->model)->user_can_moderate($iter))
			throw new \UnauthorizedException();

		$builder = $this->createFormBuilder($iter, ['csrf_token_id' => 'event_accept_' . $iter['id']])
			->add('private', CheckboxType::class, [
				'label'    => __('Only visible to members'),
				'required' => false,
			])
			->add('extern', CheckboxType::class, [
				'label'    => __('This event is not organised by Cover'),
				'required' => false,
			])
			->add('submit', SubmitType::class, ['label' => 'Accept event']);
		$builder->get('private')->addModelTransformer(new IntToBooleanTransformer());
		$builder->get('extern')->addModelTransformer(new IntToBooleanTransformer());
		$form = $builder->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			$this->model->accept_proposal($iter);

		return $this->view->redirect($this->generate_url('calendar', ['view' => 'moderate']));
	}

	public function run_moderate_reject(\DataIterAgenda $iter)
	{
		if (!get_policy($this->model)->user_can_moderate($iter))
			throw new \UnauthorizedException();

		$form = $this->createFormBuilder()
			->add('reason', TextareaType::class, [
				'label' => __('Reason for rejection'),
				'required' => false,
				'help' => __('This will be emailed to the committee.'),
			])
			->add('submit', SubmitType::class, ['label' => 'Reject event'])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
		{
			/* Remove agendapunt and inform owner of the agendapunt */
			$this->model->reject_proposal($iter);
			
			$data = $iter->data;
			$data['member_name'] = member_full_name(null, IGNORE_PRIVACY);
			$data['reason'] = $form->get('reason')->getData();

			$subject = 'Rejected event: ' . $iter['kop'];
			$body = parse_email('agenda_cancel.txt', $data);
			
			$committee_model = get_model('DataModelCommissie');
			$email = get_config_value('defer_email_to', $committee_model->get_email($iter['committee_id']));

			mail($email, $subject, $body, "From: Study Association Cover <noreply@svcover.nl>\r\n");

			$_SESSION['alert'] = sprintf(
				__('The %s has been notified that their event has been rejected.'),
				$committee_model->get_naam($iter['committee_id'])
			);
			return $this->view->redirect($this->generate_url('calendar', ['view' => 'moderate']));
		}

		return $this->view->render('confirm_reject.twig', ['iter' => $iter, 'form' => $form->createView()]);
	}

	public function run_rsvp_status($iter)
	{
		// If the id's for agenda items had been consistend, we could have stored attendance locally.
		// Now, that would be a giant hack. Therefore, I defer that to some other moment in time.

		if (!get_config_value('enable_facebook', false))
			return;

		if (!$iter['facebook_id'])
			return;

		require_once 'src/services/facebook.php';
		$facebook = get_facebook();

		if (!$facebook->getUser())
			throw new \Exception('Could not get facebook user. Please try to reconnect your Facebook account.');

		switch ($_POST['rsvp_status'])
		{
			case 'attending':
			case 'maybe':
			case 'declined':
				$result = $facebook->api(sprintf('/%d/%s' , $iter['facebook_id'], $_POST['rsvp_status']), 'POST');
				break;

			default:
				throw new \Exception('Unknown rsvp status');
		}

		return $this->view->redirect($this->generate_url('calendar', [$this->_var_id => $iter['id']]));
	}

	public function run_webcal()
	{
		$cal = new \WebCal_Calendar('Cover');
		$cal->description = __('All activities of study association Cover');

		$fromdate = new \DateTime();
		$fromdate = $fromdate->modify('-1 year')->format('Y-m-d');

		$punten = array_filter($this->model->get($fromdate, null, true), [get_policy($this->model), 'user_can_read']);
		
		$timezone = new \DateTimeZone('Europe/Amsterdam');

		foreach ($punten as $punt)
		{
			if (!get_policy($this->model)->user_can_read($punt))
				continue;

			$event = new \WebCal_Event;
			$event->uid = $punt->get_id() . '@svcover.nl';
			$event->start = new \DateTime($punt['van'], $timezone);

			if (empty($punt['tot']) || $punt['van'] == $punt['tot']) {
				$event->end = new \DateTime($punt['van'], $timezone);
				$event->end->modify('+ 2 hour');
			} else {
				$event->end = new \DateTime($punt['tot'], $timezone);
			}
			
			$event->summary = $punt['extern']
				? $punt['kop']
				: sprintf('%s: %s', $punt['committee__naam'], $punt['kop']);
			$event->description = markup_strip($punt['beschrijving']);
			$event->location = $punt->get('locatie');
			$event->url = $this->generate_url('calendar', ['agenda_id' => $punt->get_id()], UrlGeneratorInterface::ABSOLUTE_URL);
			$cal->add_event($event);
		}

		$external_url = get_config_value('url_to_external_ical');

		if ($external_url){
			try {
				$external = file_get_contents($external_url);
				$cal->inject($external);
			} catch (\Exception $e) {
				// if something goes wrong, just don't merge with external agenda
			}
		}

		$cal->publish('cover.ics');
		return null;
	}

	public function run_suggest_location()
	{
		$limit = isset($_GET['limit'])
			? (int) $_GET['limit']
			: 100;

		$locations = $this->model->find_locations($_GET['search'], $limit);

		return $this->view->render_json($locations, $limit);
	}

	public function run_subscribe()
	{
		return $this->view->render('subscribe.twig');
	}

	public function run_slide()
	{
		$events = $this->model->get_agendapunten();
		$events = array_filter($events, array(get_policy($this->model), 'user_can_read'));
		return $this->view->render('slide.twig', ['iters' => $events]);
	}

	public function get_parameter($key, $default=null)
	{
		// Compatibility
		if ($key == 'view' && parent::get_parameter('format') == 'webcal')
			return 'webcal';
		return parent::get_parameter($key, $default);
	}
}
