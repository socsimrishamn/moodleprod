# Course Guide #

It is often in charge of the main course teacher to create a guide for the student. Guides are created in different way by teachers and added in Moodle in different places.
They can end up to be inconsistent and difficult to find.


This plugin allows a teacher to create a guide. However the teacher cannot create his own "free style" guide, when tutors add a Course guide activity in their course, they must pick a template.

The Moodle administrators create the templates using the template generator. This template generator is actually a Moodle form generator allowing to create form/template containing textarea input, and file uploader (i.e. file manager).


## Demo ##
TO BE DONE

## Installation ##
install the plugin in mod/courseguide


## Templating placeholder ##
The templates allow you to enter placeholder that will generate form elements to fill up for the
teacher creating a guide.

Supported placeholders:
* file manager: [X:files]
* editor: [X:html]
* editor with default content: [X:html:BEGIN] some default HTML [X:html:END]
* link: [X:link]

X shoud be unique for each placeholder (appart [X:html:BEGIN] and [X:html:END] which share the same X value.)

## Compatibility ##
The plugin has been developed for Moodle 2.8 / MySQL / PHP 5.5.


## License ##

Coventry University

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.