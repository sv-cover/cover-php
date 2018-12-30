# Bulma Implementation ToDo

- [-] \_layout
    - [-] widgets
        - [ ] agenda.twig
        - [*] banners.twig
        - [ ] committee-battle-header.twig
        - [*] login.twig
        - [*] menu.twig
        - [ ] menuitem.twig (deprecated?)
        - [ ] poll.twig
        - [ ] promotional-header.twig
        - [*] search.twig
    - [ ] 401_unauthorized.twig
    - [ ] 404_not_found.twig
    - [ ] 500.twig
    - [*] editable.twig
    - [*] layout.twig
    - [ ] layout_without_menu.twig
- [*] actieveleden
    - [*] index.twig
- [ ] agenda
- [*] almanak
    - [*] index.twig
- [ ] announcements
- [ ] besturen
- [*] boeken
    - [*] go_to_log_in.twig
    - [*] go_to_webshop.twig
- [ ] commissies
- [ ] committeebattle
- [*] forum
    - [*] \_poll.twig
    - [*] confirm_delete_message.twig
    - [*] forum.twig
    - [ ] forum_form.twig (deprecated)
    - [*] index.twig
    - [*] poll_form.twig
    - [*] reply_form.twig
    - [*] thread.twig
    - [*] thread_confirm_delete.twig
    - [*] thread_form.twig
- [ ] fotoboekreacties
- [ ] homepage
- [ ] lidworden
- [ ] mailinglijsten
- [ ] profiel
- [ ] search
- [-] sessions
    - [*] \_login_widget.twig
    - [*] inactive.twig
    - [*] login.twig
    - [*] logout.twig
    - [*] overrides.twig
- [ ] settings
- [*] show
    - [*] form.twig
    - [*] single.twig
- [ ] signup
- [ ] stickers
- [*] wachtwoordvergeten
    - [*] request_form.twig
    - [*] reset_form.twig
- [ ] weblog


# Bulma Implementation Issues


## JS needed

- Mobile menu collapse
- Show/hide search intelligently
- Clickable (toggable) dropdowns. This would improve UX for complex dropdowns
    - _layout/widgets/menu.twig (mainly login)
    - forum/thread.twig (move thread)
- Tabs. Switch between tabbed content
    - forum/poll_form.twig
    - forum/reply_form.twig
    - forum/thread_form.twig
- Preview (page) content
    - forum/poll_form.twig
    - forum/reply_form.twig
    - forum/thread_form.twig
- Search form (like wiki/sd)
- Modals
    - Impersonate
    - [maybe] Forum delete thread/message
    - [maybe] Forum edit message
- Autocomplete
    - session/overrides.twig (autocomlete member)


## Bulma extensions / custom CSS

- Vertically padded container. Sometimes a container element for vertical grouping would be nice.
    - Pagination bar in:
        - forum/forum.twig
        - forum/thread.twig
    - Poll in forum/_poll.twig
- Divider (like the one of semantic ui)
    - sessions/_login_widget.twig (to separate the form from the become a member button)
    - boeken/go_to_login.twig
- Non-hidden mobile navbar options (like search, login, apps, hamburger). A bit like JFV does it (or Google)
- Narrow content container for improved readability. Probably with TOC sidebar.
- Almanak rendering. Last row is weird, images are not centered.
- Tabs in form field (for preview rendering) should be closer to field in:
    - forum/poll_form.twig
    - forum/reply_form.twig
    - forum/thread_form.twig
- Bulma typography is inconsistent across a single page.
- Fieldset in: 
    - sessions/overrides.twig


## Other

- Remove profile signature, it isn't used and didn't fit in the design
- Remove language switch from profile
- Almanac form is weird. May need some backend improvements (filter on filter). 
- Titles in pages behave weird. Maybe some backend improvements?
- Transpose columns in session/overrides.twig


# Pending Design Decisions

- Inline login form or separate page?
- Rounded profile pictures in Almanak? Forum?
- Depracate weblog?
