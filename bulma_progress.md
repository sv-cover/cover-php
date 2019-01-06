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
- [*] announcements
    - [*] announcements.twig
    - [*] confirm_delete.twig
    - [*] form.twig
    - [*] index.twig
    - [*] single.twig
- [*] besturen
    - [*] form.twig
    - [*] index.twig
- [*] boeken
    - [*] go_to_log_in.twig
    - [*] go_to_webshop.twig
- [*] commissies
    - [*] \_agenda.twig
    - [*] \_contact.twig
    - [*] \_foto.twig
    - [*] \_leden.twig
    - [*] \_leden_gezocht.twig
    - [ ] \_navigation.twig (deprecated?)
    - [*] archive.twig
    - [*] confirm_delete.twig
    - [*] form.twig
    - [*] index.twig
    - [*] single.twig
    - [*] working_groups.twig
- [*] committeebattle
    - [*] committee.twig
    - [*] confirm_delete.twig
    - [*] form.twig
    - [*] index.twig
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
- [-] fotoboek
    - [*] \_books.twig
    - [*] \_path.twig
    - [-] \_photos.twig
    - [*] \_random_photos.twig
    - [*] \_recent_comments.twig
    - [-] add_photos.twig
    - [*] competition.twig
    - [*] confirm_delete.twig
    - [*] people.twig
    - [*] photobook.twig
    - [*] photobook_confirm_download.twig
    - [*] photobook_form.twig
    - [*] privacy.twig
    - [-] single.twig
- [*] fotoboekreacties
    - [*] \_comment.twig
    - [*] \_form.twig
    - [*] confirm_delete.twig
    - [*] form.twig
    - [*] index.twig
    - [*] single.twig
- [ ] homepage
- [ ] lidworden
- [ ] mailinglijsten
- [ ] profiel
- [*] search
    - [*] \_single_agendapunt.twig
    - [*] \_single_announcement.twig
    - [*] \_single_committee.twig
    - [*] \_single_forum_message.twig
    - [*] \_single_fotoboek.twig
    - [*] \_single_member.twig
    - [*] \_single_page.twig
    - [*] \_single_wiki.twig
    - [*] index.twig
- [*] sessions
    - [*] \_login_widget.twig
    - [*] inactive.twig
    - [*] login.twig
    - [*] logout.twig
    - [*] overrides.twig
- [*] settings
    - [*] confirm_delete.twig
    - [*] form.twig
    - [*] index.twig
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
    - Delete announcement
    - [maybe] Edit announcement
    - Award / edit / delete committee battle points
    - download photo album
    - delete photo album button in album icon
    - photo visibility form (photos in photos of member book)
    - view photo modal
- Autocomplete
    - session/overrides.twig (autocomlete member)
    - committeebattle/form.twig (member)
- Whatever is happening in search results :P
- Whatever is happening on the committee form
- All the things fotoboek/photobook.twig is doing to make the life of the photocee easier
- Everything in fotoboek/\_photos.twig
- Change photo name (photocee functionality)
- Fotoboek/single.twig
- Add photo's to photobook


## Bulma extensions / custom CSS

- Vertically padded container. Sometimes a container element for vertical grouping would be nice.
    - Pagination bar in:
        - forum/forum.twig
        - forum/thread.twig
    - Poll in forum/_poll.twig
    - Search statistics in search/index.twig.
    - Individual parts of commissies/archive.twig
    - Single photo fotoboek/single.twig
- Divider (like the one of semantic ui)
    - sessions/_login_widget.twig (to separate the form from the become a member button)
    - boeken/go_to_login.twig
- Non-hidden mobile navbar options (like search, login, apps, hamburger). A bit like JFV does it (or Google)
- Narrow content container for improved readability. Probably with TOC sidebar.
- Almanak rendering. Last row is weird, images are not centered.
    - almanak/index.twig
    - commissies/_leden.twig
    - commissies/working_groups.twig
- Tabs in form field (for preview rendering) should be closer to field in:
    - forum/poll_form.twig
    - forum/reply_form.twig
    - forum/thread_form.twig
- Bulma typography is inconsistent across a single page.
- Fieldset in: 
    - sessions/overrides.twig
- Calendar icon in:
    - search/\_single_agendapunt.twig
    - commissies/_agenda.twig
- Bulma dl styling is ugly
- Multiline select is broken
- Static button disables title attribute (css: `pointer-events: none`). Fix this or find something better for photobook visibility in book icons.
- Lines of media objects are not always wished for, especially in nested media objects.
    - comments on photobook main page
- A bootstrap style "link" button may be desired
    - like button in photo book comments, has to be button because form. But the rest are true links. Looks fine now, except on hover.
    - like button on photo
- Level-right doesn't work if there's no level-left
- Sometimes, an inline element doesn't have space around it if there's space in the HTML, triggering a need for `&nbsp;`
    - commissies/index.twig
    - fotoboek/\_path.twig
    - fotoboek/single.twig
    - fotoboekreacties/\_comment.twig


## Other

- Remove profile signature, it isn't used and didn't fit in the design
- Remove language switch from profile
- Almanac form is weird. May need some backend improvements (filter on filter). 
- Titles in pages behave weird. Maybe some backend improvements?
- Transpose columns in session/overrides.twig and committeebattle/form.twig
- Former boards page is weird / unintuitive. Best solution: improve committees/groups and make it more automated.
- Old committee page was better. But at least it's bulma now :)
- Single committee page is crap now.
- Working groups page needs major redesign.
- Photo origin path in fotoboek/\_path.twig is ugly html.
- Redesign photobook: rendering of books could be better. May need some backend changes. Photo modal still needs to be implemented, and better than in the previous design (more mobile friendly).



# Pending Design Decisions

- Inline login form or separate page?
- Circular or square profile pictures in Almanak? Forum? Committee pages?
- Deprecate weblog?
- Standardise edit/delete/whatever buttons (announcements vs forum vs editable)
- If a committee member stops being a member, they are invisible to non-admins on the committee page. Is this a good idea?
- The head>title separator is weird (::) and I hate it.
- How to display metadata? Eg. forum messages, announcements, search results, photo album
