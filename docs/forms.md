# Forms

Forms are defined and rendered using the Symfony Form component (as of July 2023 we're using version 5.4). This document outlines available documentation, specifics on our implementation, additional notes, and some examples (recipes) of ways to tackle problems.


## Documentation
The Symfony Form component is reasonably well documented by Symfony.

- [Stand-alone documentation](https://symfony.com/doc/current/components/form.html) This documentation is most applicable to our current situation.
- [Integrated documentation](https://symfony.com/doc/current/forms.html) Documentation on Forms component integrated in the Symfony framework. Not as useful to us as the stand-alone version, but might be interesting nonetheless.
- [Built-in form types](https://symfony.com/doc/current/reference/forms/types.html) List of form types provided by Symfony. These are used to define fields.
- [Built-in constraints](https://symfony.com/doc/current/reference/constraints.html) List of validation constraints provided by Symfony.
- [Symfony Form GitHub](https://github.com/symfony/Form) Not for the faint of heart, but sometimes necessary as Symfony doesn't provide much documentation for very specific situations.

## Our implementation
Our implementation doesn't deviate too much from the standard Symfony Form implementation, other than the occasional extension. Our [`Controller`](/src/framework/controller/Controller.php) even offers a similar API for building forms as Symfony's controller by defining a `createForm` and `createFormBuilder` with the same signatures.

Where in the codebase to define forms might be somewhat counter intuitive. Here's the current logic: if a FormType directly corresponds with a DataModel (or DataIter) it is defined in [`/src/Form`](/src/Form), otherwise it is defined in the controller that uses it. If a form does not correspond to a DataModel but needs to be used across multiple controllers, it may also be defined in `/src/Form`, but does not occur at the moment.

### Integration with DataIter
Our "ORM" (that might be too generous) was not designed with Symfony Form in mind, so occasionally some effort is needed to make it work. Mostly when converting date(time) fields and booleans.

For date(time) fields, DataModel (and DataIter) usually expect strings, but Symfony Form wants DateTime types. For this reason, we define a [`StringToDateTimeTransformer`](/src/Form/DataTransformer/StringToDateTimeTransformer.php) which is essentially a reversed [`DateTimeToStringTransformer`](https://github.com/symfony/form/blob/6.3/Extension/Core/DataTransformer/DateTimeToStringTransformer.php).

Boolean fields are sometimes (but not always) defined as a `smallint` in the database and throughout the code. Symfony Form doesn't like that, so we define an [`IntToBooleanTransformer`](/src/Form/DataTransformer/IntToBooleanTransformer.php).

An example of both of these transformers in use can be seen in [`EventType`](/src/Form/EventType.php).

Note that constraint validation is harder for fields that use a transformer, as transformers are run before constraints (see Notes).

In some cases, it might be beneficial to disable mapping of fields entirely and instead handle the situation manually. For an example, see the members field in [`CommitteeType`](/src/Form/CommitteeType.php) and the [`CommitteesController`](/src/controllers/CommitteesController.php).

### Rendering
Forms can be rendered using the form-related functions in [Twig](https://twig.symfony.com/doc/2.x/). To match the design of the website, rendering is customised in the [`bulma_layout.html.twig`](/public/views/_layout/form/bulma_layout.html.twig) template, which is based on Symfony's [form div layout](https://github.com/symfony/twig-bridge/blob/6.1/Resources/views/Form/form_div_layout.html.twig) and custom types are rendered using [`custom_types.html.twig`](/public/views/_layout/form/custom_types.html.twig). We've got some extensions to go along with these customisations, more on those can be found in the Extensions.

### Form Types
- [`CommitteeIdType`](/src/Form/Type/CommitteeIdType.php): Render a ChoiceType to pick committees. Additional options:
    - `show_all` (default false): Renders all committees regardless of permissions. Use carefully.
    - `show_own` (default true): Renders a list with the members own committees at the top.
- [`FilemanagerFileType`](/src/Form/Type/FilemanagerFileType.php): Renders a filepicker field that sources from the [Cover filemanager](https://filemanager.svcover.nl/).
- [`MarkupType`](/src/Form/Type/MarkupType.php): Renders a field for content with [markup](https://wiki.svcover.nl/documentation/website/markup), with preview option.
- [`MemberIdType`](/src/Form/Type/MemberIdType.php): Renders an autocomplete field for picking members.
- [`PresentationType`](/src/Form/Type/PresentationType.php): Renders markup in form for presentation. Mainly intended for use in the SignUp form system. Question yourself if you think you need it anywhere else.

### Contraints
- [`FilemanagerFile`](/src/Validator/FilemanagerFile.php): Validates whether the chosen file has an extension allowed by the `filemanager_image_extensions` config value.
- [`Member`](/src/Validator/Member.php):  Validates whether an integer is a Member ID.

NB. Why is there a `Member` constraint but no `Committee` constraint? It's because the `CommitteeIdType` uses a ChoiceType which guarantees a valid option is picked.

### Extensions
We define several extensions, but for ease of understanding I'll only list their effects in this section.

Instead of marking required fields, we mark optional fields to provide [a better user experience](https://uxmovement.com/forms/the-optimal-way-to-mark-optional-form-fields/). In some cases, it is however desirable to mark required fields instead or disable any markings. This can be controlled using the `optional_tag` (default: true) and `required_tag` (default: false) field configuration options in the form definition or from templates (in PHP or Twig). The defaults are inverted for checkboxes, as marking required checkboxes is more user friendly.

The colour of a button is controlled with the `color` configuration value. Submit buttons are "primary" (`.button.is-primary`) by default, and other buttons are default (`.button`). For a delete option, a "danger" button (`.button.is-danger`) is more appropriate, which can be achieved using `[color => 'danger']` (in PHP or Twig).

Bulma [wraps select fields](https://bulma.io/documentation/form/select/). Attributes on that wrapper can be configured using the `wrapper_attr` configuration (in PHP or Twig). This way, you could for example add any classes to your select field: `['wrapper_attr' => ['class' => 'is-primary is-fullwidth']]`.

Symfony provides the option to render ChoiceTypes expanded, as checkbox or radio groups. For better UX, these can be rendered as [chips](https://uxmovement.com/forms/why-chips-should-replace-checkboxes-and-radio-buttons/). These work, however, best for short labels and are therefore optional. A ChoiceType field can be rendered as chips by setting both `expanded` and `chips` to true in the field configuration (in PHP or Twig).

Bulma wraps checkboxes (and radio buttons) in a label and our rendering does that by default. This can be disabled by setting `render_label` to false in PHP or Twig.

In some situations, a checkbox should be rendered as a switch for [better user experience](https://uxplanet.org/checkbox-vs-toggle-switch-7fc6e83f10b8). To do so, set `switch` to true in PHP or Twig. This will render a switch using the `.switch.is-rounded` class. You can add additional classes to the `attr` configuration for more options. Read the linked blog post for documentation on when a switch is appropriate.

Bulma allows for customisation of the "call to action" on the button of a file input. This can be customised by setting the `cta` (default: `'Browse…'`) configuration option (in PHP or Twig).


## Notes
I've ran into the following while implementing Symfony Form in the website. It might be useful knowledge to you as well.

- All transformers (view and model) are run before constraints are evaluated. This means that some constraints that depend on certain date types might not work when using transformers. For example, you cannot do date comparisons (Range, GreaterThan, etc.) when using the `StringToDateTimeTransformer`. This can be useful in some cases: [`EventType->validate_facebook_id`](/src/Form/EventType.php).
- In theory, Symfony should be able to guess HTML5 attributes (such as min, max, or pattern) from the defined constraints. I've not been able to get that to work, and relying on this seems to be [discouraged anyway](https://github.com/symfony/symfony/issues/30694#issuecomment-650830738). So it's best to configure these attributes explicitly in the `attr` configuration.

## Recipes and examples
This section contains examples of implementations that might not be straight-forward or intuitive. These are listed by stating a problem or goal and referring to forms, controllers and/or templates that implement a solution.

### DateTimes are strings in the DB, but Symfony wants DateTimes
See "Integration with DataIter". This is solved using the [`StringToDateTimeTransformer`](/src/Form/DataTransformer/StringToDateTimeTransformer.php). Examples are [`EventType`](/src/Form/EventType.php), [`CommitteeType`](/src/Form/CommitteeType.php), [`PhotoBookType`](/src/Form/PhotoBookType.php), [`SignupFormType`](/src/Form/SignupFormType.php) and [`VacancyType`](/src/Form/VacancyType.php). Keep in mind this might cause issues with constraints (see notes).

### Booleans are integers in the DB, but Symfony wants booleans
See "Integration with DataIter". This is solved using the [`IntToBooleanTransformer`](/src/Form/DataTransformer/IntToBooleanTransformer.php). Examples are [`EventType`](/src/Form/EventType.php) and [`PartnerType`](/src/Form/PartnerType.php). Keep in mind this might cause issues with constraints (see notes).

### The database doesn't like Symfony (or reversed)
This might happen for many different types, but often [Symfony's CallbackTransformer](https://symfony.com/doc/current/form/data_transformers.html#example-1-transforming-strings-form-data-tags-from-user-input-to-an-array) is the answer. For example, [`EventType`](/src/Form/EventType.php) uses it to convert between Facebook event ID and URL,  [`MailinglistType`](/src/Form/MailinglistType.php) uses it to make sure email addresses are always lowercase, [`StickerType`](/src/Form/StickerType.php) uses it to convert between doubles and strings, and [`RegistrationType`](/src/Form/RegistrationType.php) uses it to do some input cleaning. Keep in mind this might cause issues with constraints (see notes).

### Can I add transformers from controllers?
Yes, this happens for example in [`CommitteesController`](/src/controllers/CommitteesController.php), [`CommitteesController`](/src/controllers/CommitteesController.php) 

### Validation of one field depends on the value of another
A good way to do this is by using a [Callback constraint](https://symfony.com/doc/current/reference/constraints/Callback.html) to validate the entire form. Examples of this can be seen in [`EventType`](/src/Form/EventType.php), and [`VacancyType`](/src/Form/VacancyType.php). Another but less preferred way to do this is by using an event listener on the `FormEvents::SUBMIT` event, this can be seen in the [`MailingListsController`](/src/controllers/MailingListsController.php).

### Validation of one field depends on data from the DB
This can be done with a [Callback constraint](https://symfony.com/doc/current/reference/constraints/Callback.html). Examples can be found in [`EventType->validate_datetime`](/src/Form/EventType.php), [`PasswordController`](/src/controllers/PasswordController.php), [`PhotoBooksController`](/src/controllers/PhotoBooksController.php), and [`ProfileController`](/src/controllers/ProfileController.php).

### There might be an error on the form or a field, but I won't know until the data is being processed
You can still add errors and invalidate the form after calling `$form->isValid()`. This is done in [`CommitteesController`](/src/controllers/CommitteesController.php) and [`SessionsController`](/src/controllers/SessionsController.php).

### Some fields should (not) be rendered based on permissions or DataIter
Have a look at [`PageType`](/src/Form/PageType.php)!

### My ChoiceType choices depend on the DataIter
Have a look at [`SignupFormType`](/src/Form/SignupFormType.php)!

### Can I add/remove fields based on view?
Yes! Fields are added to forms in the [`CommitteesController`](/src/controllers/CommitteesController.php) and [`SignupFormsController`](/src/controllers/SignupFormsController.php). There's also a `remove` function that works the same way, but we never use it.

### Can I provide default values?
Yes! If you have a DataIter, take a look at [`CommitteesController`](/src/controllers/CommitteesController.php), [`MailingListsController`](/src/controllers/MailingListsController.php), [`PhotoCommentsController`](/src/controllers/PhotoCommentsController.php) or [`StickersController`](/src/controllers/StickersController.php). If you don't have an iter, just pass an array with defaults instead, as is done in [`ClubsController`](/src/controllers/ClubsController.php) or [`MembershipController`](/src/controllers/MembershipController.php), for example.

### I need to render a form that's processed by a different controller
Have a look at the [sessions tab](/public/views/profile/sessions_tab.twig) of the profile page, which is processed by [`SessionsController`](/src/controllers/SessionsController.php). Or at the [photo comments form](/public/views/photo_comments/_form.twig) , which is processed by [`PhotoCommentsController`](/src/controllers/PhotoCommentsController.php).

### I need to perform an action based on selected iters
Have a look at [`MailingListsController->run_unsubscribe`](/src/controllers/MailingListsController.php) and [`mailinglists/single.twig`](/public/views/mailinglists/single.twig) or at [`SignupFormsController->run_delete_entries`](/src/controllers/SignupFormsController.php) and [`sigunup/list_entries.twig`](/public/views/sigunup/list_entries.twig).
