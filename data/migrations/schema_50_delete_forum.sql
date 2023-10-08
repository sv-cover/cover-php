DROP TABLE forums, forum_acl, forum_group, forum_group_member, forum_header,
            forum_lastvisits, forum_threads, forum_messages, forum_sessionreads,
            forum_visits, pollopties, pollvoters;
DELETE FROM configuratie WHERE configuratie.key LIKE '%forum%';