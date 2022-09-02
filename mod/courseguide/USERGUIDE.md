## Introduction
This plugin allows guide to be created by tutors and accessed by students in a structured consistent manner.

* a guide is a Moodle activity.
* a guide is created by a teacher and is based on a template created by an administrator.
* the template creation system allows the generation of Moodle forms containing textarea, file uploader and links.

It is the responsibility of each main course teacher to create a course guide for the students. However, a teacher cannot create his own "free style" guide, he needs to follow a template created by the Moodle administrator. The plugin template creator allows administrators to create templates using placeholders for text area, file manager and links (a placeholder is like a Wordpress shortcode).

On another hand, a Moodle site is sometimes used to manage multiple educational organizations. The Course Guide plugin allows administrators to restrict access to the templates and guides as needed.

## Moodle Administrator

The administrator role is to install the activity plugin, set up the plugin in Moodle and provide the teachers with guide templates.

### Download

From the Moodle plugins database.

### Installation

install the plugin files in a mod/courseguide.

### Create a template

The teachers cannot create a guide if you don’t provide them a guide template.

Go to Site Administration > Plugins > Activity Modules > Course Guide > Manage templates

Click on Add template.

Enter the following:

* template name: when creating a guide, the teacher must select a template so you need to give it a name.
* Display mode: a guide can be displayed as other Moodle activities (a link on the course page that redirect to the Course Guide activity), or the guide content is displayed inside the main course page itself, and finally the guide content is displayed in the main course page itself by in a collapsable section so by default it doesn’t take too much space.
* Default display mode: the default display mode when creating a guide. Note that the default display mode is not in the selected display mode, then the default display mode is ignored.
* default guide name: it will prefilled the Guide name when a teacher creates a guide. The teacher still can change the guide name
* organization: you can restrict the visibility of a template (and the guide based on this template) per organisation. See the section about organization
* description: some notes about the template, only seen by other administrator when they edit a template
* template: this is the guide template. All text and images you enter will be displayed in the guide created by the teachers. The teachers won’t be able to edit the text and image you enter this way. To allow teachers to enter text and image when they create a guide, you must type an editor placeholder

### Placeholder types
The templates allow you to enter placeholders. When the guide creation form is display to a teacher, these placeholders are replaced by respective form elements (input text, input link, input files).

[X:files] : the teacher will be asked to add files in a Moodle file manager.

[X:html] : the teacher will be asked to enter text and images in the Moodle HTML editor.

[X:html:BEGIN] some default HTML [X:html:END] : the teacher will be asked to enter text and images in the Moodle HTML editor. You can pre-fill this editor with some default text and images.

[X:link] : the teacher will be asked to enter a web page link.

In the placeholder syntax, X must be an integer and it should be unique for each placeholder (apart from [X:html:BEGIN] and [X:html:END] which share the same X value.). X is an identifier used internally by the code - it has no use for you but it is required otherwise the block will not properly display the guides.

### Preview a template

Once a template has been created you can preview the guide creation page when the teacher selects this template. Click on the Preview link.

### Hide / Show a template

When a template is hidden no teacher can create a new guide using this template. However hidden a template has no impact on the visibility of guides based on this template (guide visibility can be changed in the Manage guides pages). When a template is shown then teachers can use it to create new guides. Click on Hide / Show in the Manage templates page.

### Access the template guides

If guides have been created using a template, you can access all guides of this template clicking on the respective Guides link.

### Edit a template

Editing a template is similar to adding a template. Note that if at least one guide has been created using a template then you can not edit the template field (but you can still edit the name, default name, display mode, default display mode (if the current display mode use by a guide is not selected anymore, then the guide will automatically have the default display mode), description and organization). Click on Edit in the Manage templates page.

### Delete a template

You can delete a template. If you do it, then all guides created with this template will be deleted too. Click on Delete in the Manage templates page.

### Add / Edit / Delete guides

Go to Site Administration > Plugins > Activity Modules > Course Guide > Manage templates
You can click on the Guides links for each template having at least one guide.
In the Manage guides page you have direct access to the guide, the edit page and delete page.

###  Restricting access per organization
If your Moodle site is used to manage multiple organizations it is possible to restrict access to template and guides to a specific organization.

1. Create a new user profile field (see the Moodledocs documentation on how to create a profile fields). For our example, call the profile field shortname “faculty”
2.  Set the “faculty” profile field to the users you want to restrict the access (For example it could be “Faculty of Science”)
3. Go to Site Administration > Plugins > Activity Modules > Course Guide
4. Type “faculty” is the organization field (it must be a profile field shortname).
5. Go to Site Administration > Plugins > Activity Modules > Course Guide > Manage templates
6. Edit all templates you want to restrict to one specific faculty. In the organisation field enter the faculty (for example “Faculty of Science”)

From this point you have restricted access per organization. It has the following impact:

* when the teacher profile field is empty (i.e. the teacher “Faculty” profile field is empty), the teacher can create guides based on any templates.
* when the student profile field is empty , the student can see any guide.
* when a template has no organization, all teachers can create a guide based on this template and the guide can be viewed by student.
* when the template organization matches the teacher profile field (i.e. the teacher “Faculty” profile field is equal to “Faculty of Science”), the teacher can create guides based on this specific template.
* when the template organization matches the student profile field, the student can see the guide based on this specific template.
* in all other cases, the teachers cannot create a guide and the guides can not be viewed by student.

## Teachers

As a Teacher your role is to create a Guide for the student.

### Create a guide

In a course section, click on add activity and select Course Guide.

[screenshot]

On this page you first must select a guide Template. Guide template are preformatted guide forms. They contains hard coded text and images you are not allowed to modified (often it would be the header and footer of the guide which your institutions may want to keep control of). They usually also contains at least one input field for you to enter your own text and images. Sometimes you can even upload files or enter links.

Once you selected your guide template, enter a Name. Sometimes a field would have been prefilled to help you following a good naming convention.

Finally you can create your guide content filling the text editor with text and images, uploading files in the file managers and creating link. These inputs will changed depending on the template you selected.

### Update a guide

You can update a guide activity at any time. 

### Delete a guide

You can delete a guide activity at any time.

### Hide / Show a guide

You can hide/show a guide activity at any time.

## Students

As a student your role is to read the guides. A guide can have three guide display mode.

Click on a Guide to view it if it is not already display on the main course page. Guides may contain text, images, links and files to download.


