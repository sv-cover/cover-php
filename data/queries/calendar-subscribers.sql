SELECT
	l.id,
	l.voornaam,
	l.tussenvoegsel,
	l.achternaam,
	s.ip_address,
	CURRENT_TIMESTAMP - s.last_active_on as ago
FROM sessions s
RIGHT JOIN leden l ON
	l.id = s.member_id 
WHERE
	s.application = 'calendar'
	AND s.last_active_on > CURRENT_TIMESTAMP - INTERVAL '1 WEEK'
ORDER BY
	ago ASC
