import numpy as np
import cv2
import psycopg2
import urllib2
import sys
import os

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


def crop_image(src, x, y, w, h):
	ih, iw = src.shape
	sx = int(iw * x)
	sy = int(ih * y)
	sw = int(iw * w)
	sh = int(ih * h)

	# square it
	if sw > sh:
		sy = sy - (sw - sh) / 2
		sh = sw
	elif sh > sw:
		sx = sx - (sh - sw) / 2
		sw = sh

	# make it fit inside the image
	if sx + sw > iw:
		sx = sx - ((sx + sw) - iw)

	if sy + sh > ih:
		sy = sy - ((sy + sh) - ih)

	assert sx >= 0 and sx <= iw, "Value of sx (%d) is out of bounds" % sx
	assert sy >= 0 and sy <= ih, "Value of sy (%d) is out of bounds" % sy

	if sh < 20:
		raise Exception('cropped height smaller than 20')

	if sw < 20:
		raise Exception('cropped width smaller than 20')

	return cv2.resize(src[sy:sy+sh, sx:sx+sw], (100, 100))


cur.execute("SELECT f_f.id, f.url, f_f.lid_id, f_f.x, f_f.y, f_f.w, f_f.h, f.id FROM foto_faces f_f RIGHT JOIN fotos f ON f_f.foto_id = f.id WHERE f_f.deleted = FALSE AND f_f.lid_id IS NOT NULL ORDER BY f.id DESC");

img = None
img_url = None

for row in cur.fetchall():
	face_path = 'faces/%d/%d.png' % (row[2], row[0])

	if not os.path.isdir(os.path.dirname(face_path)):
		os.mkdir(os.path.dirname(face_path))

	# Face is already croppped?
	if os.path.isfile(face_path):
		continue

	# Load image (if not still loaded)
	if row[1] != img_url:
		img = read_image_remote(row[1])
		img_url = row[1]

	try:
		crop = crop_image(img, float(row[3]), float(row[4]), float(row[5]), float(row[6]))
		cv2.imwrite(face_path, crop)
	except:
		print("%d (face of %d in photo %d) is too small" % (row[0], row[2], row[7]))

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
