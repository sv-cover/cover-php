UPDATE pages p
   SET content_en = p.content
 WHERE p.content_en = NULL 
    OR p.content_en = '';
