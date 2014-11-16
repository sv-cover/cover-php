import numpy as np
import cv2
import os
import random

training_faces = []
training_labels = []
testing_faces = []
testing_labels = []

min_num_faces = 1
n_test_faces = 50

print("Loading...")
for member_id in os.listdir('faces'):
	faces = [face for face in os.listdir('faces/' + member_id) if face[-4:] == '.png']

	if (len(faces) < min_num_faces):
		print("Skipping %d because we only have %d faces of them" % (int(member_id), len(faces)))
		continue

	for face in faces:
		# training_faces.append('faces/' + member_id + '/' + face)
		training_faces.append(cv2.imread('faces/' + member_id + '/' + face, cv2.CV_LOAD_IMAGE_GRAYSCALE))
		training_labels.append(int(member_id))

print("Extracting a little test set...")
for test_idx in range(n_test_faces):
	idx = random.randint(0, len(training_faces) - 1)
	testing_faces.append(training_faces.pop(idx))
	testing_labels.append(training_labels.pop(idx))

def image_loader(files):
	for file in files:
		yield cv2.imread(file, cv2.CV_LOAD_IMAGE_GRAYSCALE)

print("Training on %d samples..." % len(training_faces))
model = cv2.createLBPHFaceRecognizer()
# model.train(image_loader(training_faces), training_labels)
model.train(training_faces, np.array(training_labels))

correct = 0
for test_idx in range(n_test_faces):
	print("Trying with a face of %d:" % testing_labels[test_idx])
	[predicted_label, confidence] = model.predict(testing_faces[test_idx])
	print("Found: %d (confidence: %f)" % (predicted_label, confidence))
	if testing_labels[test_idx] == predicted_label:
		correct = correct + 1

print("%d of %d correct" % (correct, n_test_faces))

