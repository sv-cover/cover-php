DELETE FROM foto_faces WHERE id IN (
SELECT f.id
FROM foto_faces f
LEFT JOIN foto_faces f2 ON
f.foto_id = f2.foto_id AND
f.id != f2.id AND
f.x > f2.x - 0.01 AND
f.x < f2.x + 0.01 AND
f.y > f2.y - 0.01 AND
f.y < f2.y + 0.01 AND
f.w > f2.w - 0.01 AND
f.w < f2.w + 0.01 AND
f.h > f2.h - 0.01 AND
f.h < f2.h + 0.01
WHERE
f.lid_id IS NULL
GROUP BY
f.id
HAVING
COUNT(f2.id) > 0)