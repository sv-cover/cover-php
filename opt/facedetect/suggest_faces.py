from __future__ import print_function, division

import psycopg2
from PIL import Image
import numpy as np
import dlib
import sys
import os
from random import random

conn = psycopg2.connect("dbname=webcie")
cur = conn.cursor()
icur = conn.cursor()

dirname = os.path.dirname(__file__)

detector = dlib.get_frontal_face_detector()
shape_predictor = dlib.shape_predictor(os.path.join(dirname, "models/shape_predictor_5_face_landmarks.dat"))
face_recognizer = dlib.face_recognition_model_v1(os.path.join(dirname, "models/dlib_face_recognition_resnet_model_v1.dat"))

def read_image(path):
	img = Image.open(path)
	img.thumbnail((800,800)) # downscale it a bit for performance
	return np.array(img), img.width, img.height


def insert_face(face, cluster_id):
	data = {
		'cluster_id': cluster_id,
		'id': face['foto_id'],
		'x': face['face'].left() / face['width'],
		'y': face['face'].top() / face['height'],
		'w': (face['face'].right() - face['face'].left()) / face['width'],
		'h': (face['face'].bottom() - face['face'].top()) / face['height']
	}
	icur.execute("""
		UPDATE foto_faces SET cluster_id = %(cluster_id)s WHERE foto_id = %(id)s
		AND x > %(x)s - 0.05 AND x < %(x)s + 0.05
		AND y > %(y)s - 0.05 AND y < %(y)s + 0.05
		AND w > %(w)s - 0.05 AND w < %(w)s + 0.05
		AND h > %(h)s - 0.05 AND h < %(h)s + 0.05
		""", data)
	if icur.rowcount == 0:
		icur.execute("""
			INSERT INTO foto_faces
			(foto_id, x, y, w, h, cluster_id)
			VALUES (%(id)s, %(x)s, %(y)s, %(w)s, %(h)s, %(cluster_id)s)
			""", data)


if __name__ == '__main__':
	if len(sys.argv) < 2:
		print("Usage: %s root photo-id [photo-id ...]" % sys.argv[0])
		exit(1)

	photos_root = sys.argv[1]

	photo_ids = [int(photo_id) for photo_id in sys.argv[2:]];

	cur.execute("SELECT id, filepath FROM fotos WHERE id = ANY (%s)", (photo_ids,));

	faces = []

	for row in cur.fetchall():
		print("{}\t{}\t" .format(*row), end='')
		try:
			img, width, height = read_image(os.path.join(sys.argv[1], row[1]))
			for face in detector(img, 1):
				shape = shape_predictor(img, face)
				descriptor = face_recognizer.compute_face_descriptor(img, shape)
				faces.append({
					'foto_id': row[0],
					'width': width,
					'height': height,
					'face': face,
					'descriptor': descriptor
				})
			print("queued")
		except:
			print("error")

	labels = dlib.chinese_whispers_clustering([face['descriptor'] for face in faces], 0.5)
	
	# Add a random offset to the cluster ids as to make them not overlap
	cluster_offset = random() * 2147483647

	for n, cluster_id in enumerate(labels):
		insert_face(faces[n], cluster_offset + cluster_id)
	
	conn.commit()

	print("Finished.")
	exit(0)

