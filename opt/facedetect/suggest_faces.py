import numpy as np
import cv2
import psycopg2
import urllib2
import sys
import os

opencv_shared = os.path.dirname(os.path.abspath(__file__)) + '/cascades/'
face_cascade = cv2.CascadeClassifier(opencv_shared + 'haarcascade_frontalface_default.xml')

conn = psycopg2.connect("dbname=webcie")
cur = conn.cursor()

icur = conn.cursor()

def read_image_remote(url):
	if url[0:2] == '//':
		url = 'https:' + url
	
	request = urllib2.urlopen(url)
	data = np.asarray(bytearray(request.read()), dtype=np.uint8)
	image = cv2.imdecode(data, cv2.CV_LOAD_IMAGE_GRAYSCALE)

	if image is None:
		raise Exception("cv.imdecode could not decode image")

	return image

def read_image(path):
	image = cv2.imread(path, cv2.CV_LOAD_IMAGE_GRAYSCALE)
	if image is None:
		raise Exception("cv.imdecode could not decode image")
	return image

def find_faces(path):
	try:
		gray = read_image(path)
		ih, iw = gray.shape
		faces = face_cascade.detectMultiScale(gray, 1.1, 4, 0, (int(iw * 0.05), int(ih * 0.05)))
		return [(float(x / float(iw)), float(y / float(ih)), float(w / float(iw)), float(h / float(ih))) for (x, y, w, h) in faces]
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
		faces = find_faces(os.path.join(sys.argv[1], row[1]))

		print("%d:" % row[0])

		for face in faces:
			print("  x: %0.2f y: %0.2f w: %0.2f h: %0.2f" % face)
			if insert_face(row[0], face):
				print("  added")
				conn.commit()
			else:
				print("  duplicate")

	print("Finished.")
	exit(0)

