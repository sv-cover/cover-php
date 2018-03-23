from __future__ import print_function, division

import psycopg2
from PIL import Image
import numpy as np
import dlib
import sys
import os

conn = psycopg2.connect("dbname=webcie")
cur = conn.cursor()
icur = conn.cursor()

detector = dlib.get_frontal_face_detector()

def read_image(path):
	img = Image.open(path)
	img.thumbnail((800,800)) # downscale it a bit for performance
	return np.array(img), img.width, img.height

def find_faces(path):
	try:
		img, width, height = read_image(path)
		return [
			(
				face.left() / width,
				face.top() / height,
				(face.right() - face.left()) / width,
				(face.bottom() - face.top()) / height
			) for face in detector(img, 1)]
	except Exception as ex:
		print("Skipping %s: %s" % (path, ex))
		return []

def insert_face(foto_id, face):
	icur.execute("""SELECT COUNT(id) FROM foto_faces WHERE foto_id = %(id)s
		AND x > %(x)s - 0.05 AND x < %(x)s + 0.05
		AND y > %(y)s - 0.05 AND y < %(y)s + 0.05
		AND w > %(w)s - 0.05 AND w < %(w)s + 0.05
		AND h > %(h)s - 0.05 AND h < %(h)s + 0.05""", {
			'id': foto_id,
			'x': face[0], 'y': face[1],
			'w': face[2], 'h': face[3]
		})

	if icur.fetchone()[0] == 0:
		icur.execute("""INSERT INTO foto_faces (foto_id, x, y, w, h) VALUES (%s, %s, %s, %s, %s)""", (foto_id,) + face)
		return True

	return False


if __name__ == '__main__':
	if len(sys.argv) < 2:
		print("Usage: %s root photo-id [photo-id ...]" % sys.argv[0])
		exit(1)

	photos_root = sys.argv[1]

	photo_ids = [int(photo_id) for photo_id in sys.argv[2:]];

	cur.execute("SELECT id, filepath FROM fotos WHERE id = ANY (%s)", (photo_ids,));

	for row in cur.fetchall():
		print("%d:" % row[0])
		for face in find_faces(os.path.join(sys.argv[1], row[1])):
			print("  x: %0.2f y: %0.2f w: %0.2f h: %0.2f" % face, end='')
			if insert_face(row[0], face):
				print("  added")
			else:
				print("  duplicate")
		conn.commit()

	print("Finished.")
	exit(0)

