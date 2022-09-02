i#!/bin/bash

API="http://localhost/stable_28_courseguide/webservice/rest/server.php"    # WS entry point.
COURSEID=3                                             # Course to duplicated.
CATEGORYID=1                                            # Category to duplicate to.
TOKEN="76a3926c4acec1f101b94105086832a5"                # WS Token.


# SCRIPT STARTS HERE.
SUFFIX=`date +"%s"`

echo "Duplicate without user data"

SHORTNAME="Duplicated${COURSEID}_NODATA_${SUFFIX}"
curl $API\
    -d moodlewsrestformat=json\
    -d wstoken=$TOKEN\
    -d wsfunction=core_course_duplicate_course\
    -d courseid=$COURSEID\
    -d fullname="Duplicated $COURSEID (without user data) - $SUFFIX"\
    -d shortname="$SHORTNAME"\
    -d categoryid=$CATEGORYID\
    -d 'options[0][name]=users'\
    -d 'options[0][value]=0'

echo
echo "Duplicate with user data"

SHORTNAME="Duplicated${COURSEID}_${SUFFIX}"
curl $API\
    -d moodlewsrestformat=json\
    -d wstoken=$TOKEN\
    -d wsfunction=core_course_duplicate_course\
    -d courseid=$COURSEID\
    -d fullname="Duplicated $COURSEID (with user data) - $SUFFIX"\
    -d shortname="$SHORTNAME"\
    -d categoryid=$CATEGORYID\
    -d 'options[0][name]=users'\
    -d 'options[0][value]=1'

echo

