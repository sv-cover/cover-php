UPDATE pages p
   SET content_en = p.content
 WHERE p.content_en IS NULL 
    OR p.content_en = '';
