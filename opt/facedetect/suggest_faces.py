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
	request = urllib2.urlopen(url)
	data = np.asarray(bytearray(request.read()), dtype=np.uint8)
	image = cv2.imdecode(data, cv2.CV_LOAD_IMAGE_GRAYSCALE)

	if image is None:
		raise Exception("cv.imdecode could not decode image")

	return image


def find_faces(url):
	try:
		gray = read_image_remote(url)
		ih, iw = gray.shape
		faces = face_cascade.detectMultiScale(gray, 1.2, 5, 0, (int(iw * 0.05), int(ih * 0.05)), (int(iw * 0.9), int(ih * 0.9)))
		return [(float(x / float(iw)), float(y / float(ih)), float(w / float(iw)), float(h / float(ih))) for (x, y, w, h) in faces]
	except Exception as ex:
		print("Skipping %s: %s" % (url, ex))
		return []

def insert_face(foto_id, face):
	icur.execute("""SELECT COUNT(id) FROM foto_faces WHERE foto_id = %(id)s
		AND x > %(x)s - 5 AND x < %(x)s + 5
		AND y > %(y)s - 5 AND y < %(y)s + 5
		AND w > %(w)s - 5 AND w < %(w)s + 5
		AND h > %(h)s - 5 AND h < %(h)s + 5""", {
			'id': foto_id,
			'x': face[0], 'y': face[1],
			'w': face[2], 'h': face[3]
		})

	# if icur.fetchone()[0] == 0:
	icur.execute("""INSERT INTO foto_faces (foto_id, x, y, w, h) VALUES (%s, %s, %s, %s, %s)""", (foto_id,) + face)
	return True

	return False

if len(sys.argv) != 2:
	print("Usage: %s book-id" % sys.argv[0])
	exit(1)

cur.execute("SELECT id, url FROM fotos WHERE boek = %s", (sys.argv[1],));

for row in cur.fetchall():
	faces = find_faces(row[1])

	print("%d:" % row[0])

	for face in faces:
		print("  x: %0.2f y: %0.2f w: %0.2f h: %0.2f" % face)
		if insert_face(row[0], face):
			print("  added!")
		conn.commit()


# img = cv2.imread('sachin.jpg')
# gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

# faces = face_cascade.detectMultiScale(gray, 1.3, 5)
# for (x,y,w,h) in faces:
#     cv2.rectangle(img,(x,y),(x+w,y+h),(255,0,0),2)
#     roi_gray = gray[y:y+h, x:x+w]
#     roi_color = img[y:y+h, x:x+w]
#     eyes = eye_cascade.detectMultiScale(roi_gray)
#     for (ex,ey,ew,eh) in eyes:
#         cv2.rectangle(roi_color,(ex,ey),(ex+ew,ey+eh),(0,255,0),2)

# cv2.imshow('img',img)
# cv2.waitKey(0)
# cv2.destroyAllWindows()
