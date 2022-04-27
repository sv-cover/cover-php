# Facedetect

## Setup

- Setup models (see models/Readme.md).
- Setup Python and Postgres.
- Set path_to_suggest_faces_log in config if you're interested in logs.
- Test by going to a photoalbum as PhotoCee, add view=people to the url and click "Re-run face detection." Check the logs to see what happened.

### Python

- Install non-python dependencies: python3-dev, libpq-dev, cmake.
- Create virtual environment.
- Install requirements.
- Set path_to_python in config, for example: `'VIRTUAL_ENV="/srv/www/website/opt/facedetect/venv" /srv/www/website/opt/facedetect/venv/bin/python`.

### Postgres

Make sure the user running the website the necessary access.

    CREATE ROLE "www-webcie" LOGIN;
    GRANT CONNECT ON DATABASE website TO "www-webcie";
    GRANT USAGE ON SCHEMA public TO "www-webcie";
    GRANT USAGE ON SEQUENCE foto_faces_id_seq TO "www-webcie";
    GRANT SELECT ON public.fotos TO "www-webcie";
    GRANT SELECT, INSERT, UPDATE ON public.foto_faces TO "www-webcie";
