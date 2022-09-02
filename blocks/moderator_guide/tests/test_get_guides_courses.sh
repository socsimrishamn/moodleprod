i#!/bin/bash

API="http://localhost/stable_28_courseguide/webservice/rest/server.php"    # WS entry point.
TOKEN="2b9a075199193186fbf2933c8e5b7efc"                # WS Token.

# SCRIPT STARTS HERE.
echo "Call block_moderator_guide_get_guides_courses"
COURSEID=3
SHORTNAME="%guide%"
ORGANISATION=""
LIMITFROM=0
LIMITNUM=3

curl $API\
    -d moodlewsrestformat=json\
    -d wstoken=$TOKEN\
    -d wsfunction=block_moderator_guide_get_guides_courses\
    -d courseid=$COURSEID\
    -d courseshortname="$SHORTNAME"\
    -d organisation=$ORGANISATION\
    -d limitfrom=$LIMITFROM\
    -d limitnum=$LIMITNUM

echo


