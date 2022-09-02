# Moderator Guide #

Sometimes a Moodle site is used to manage multiple educational faculties/schools using the same Moodle site. 
These entities are sometimes running exams and an external teacher comes aboard to grade the students. 
To put up the teachers at speed these faculties provide external teachers access to the course 
and a course guide on how to grade. It is in charge of the main course teacher to create this guide for the external teachers. 
However, the main teacher cannot create his own "free style" guide, he needs to follow a template. 


With this block your Moodle site list, on each course, the available "guides" to the external teachers who have access to the course. 
It also includes a template generator. This template generator is actually a Moodle form generator allowing to create form/template containing textarea input, 
and file uploader (i.e. file manager). 

## Demo ## 
Demo recorded the 15th November 2016:
http://youtu.be/jISpfobm8H4

## Installation ##
install the plugin in blocks/moderator_guide Add the plugin on the front page


## Block setup ##
The goal of this block is to be displayed on all course main pages. 
In order to achieve this result you need to add the course on the front page,
edit it and select display this block through the entire site. Then go to a course page.
Edit the block and select display on course main pages only.

Turn editing on and edit the block with the cloack icon. You can there change the name of the plugin.

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

## TODO ##
* use templating on Moodle 2.9 - block templating starting Moodle 3.0
* use AMD module starting Moodle 2.9
* travis integration for CI
* new placeholder as radio button or check boxes (that said, these need to be designed properly as it would not make sense to display checkboxes or radio button to the guide viewer)


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
