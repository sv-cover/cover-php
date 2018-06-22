UPDATE pages p
   SET content_en = p.content
 WHERE p.content_en IS NULL 
    OR p.content_en = '';

UPDATE committee_members
   SET functie = CASE functie WHEN 'Voorzitter' THEN 'Chairman'
                    WHEN 'Vice-voorzitter' THEN 'Vice Chairman'
                    WHEN 'Secretaris' THEN 'Secretary'
                    WHEN 'Penningmeester' THEN 'Treasurer'
                    WHEN 'Algemeen Lid' THEN 'General Member'
                    WHEN 'Fotograaf' THEN 'Photographer'
                    WHEN 'Commissaris Intern' THEN 'Commissioner of Internal Affairs'
                    WHEN 'Commissaris Extern' THEN 'Commissioner of External Affairs'
                    WHEN 'Ouderejaars Lid' THEN 'Senior Member'
                    WHEN 'Commissaris Zorgtoeslag' THEN 'Commissioner Healthcare Allowance'
                    WHEN 'Commissaris Kusje' THEN 'Commissioner Boo-boo'
                    WHEN 'Commissaris Verbanddoos' THEN 'Commissioner Bandage box'
                    WHEN 'Huisarts' THEN 'General Practitioner'
                    WHEN 'Huisarts-assistent' THEN 'GP assistant'
                    WHEN 'Commissaris Heethoofd' THEN 'Commissioner Fire Chief'
                    ELSE functie
        END
;
