#!/bin/sh
psql webcie -c "TRUNCATE $1";

ssh webcie@svcover.nl \
	-C "psql webcie -c \"COPY $1 TO STDOUT\"" \
	| psql webcie -c "COPY $1 FROM STDIN";

SEQ_VAL=`ssh webcie@svcover.nl \
	-C "psql webcie -Aqtc \"SELECT last_value FROM $1_id_seq\""`;

psql webcie -c "SELECT setval('$1_id_seq', $SEQ_VAL)";
