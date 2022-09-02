User guide
----------

**Administrator**

Setup the custom profile field restriction

1. Site Administration > Users > Accounts > User profile fields
2. Create new profile field called "Faculty" with for shortname "faculty"
3. Site Administration > Plugins > Blocks > Moderator guide
4. sets "Restrict by Custom Profile Field" to "faculty" (the shortname)

Once this custom profile field restriction is set, then the organisation field of a template will be check against the value
of each user "Faculty" profile field. If a user has no value in his "Faculty" custom field, 
then he can see all templates and guides (following his permissions). An admin can do anything.

Creates a new template

1. Site Administration > Plugins > Blocks > Moderator guide > Manage templates
2. admin clicks on "Add template"
3. admin gives it a name, default guide name, description and organisation.
4. admin uses the HTML editor to create the template. He can use placeholder for text areas, file managers and links. 
Placeholder for text areas can have default text pre-filled for the guide creator. No files can be “pre-filled”.
5. admin saves the new template.
6. Admin clicks on "Show" template.

Preview a template

1. Site Administration > Plugins > Blocks > Moderator guide > Manage templates
2. Admin clicks on "Preview" on the "HLS template" (when a template is named "HLS template").
3. The "Template" field only display "HLS template" as a choice.
3. Admin goes back to manage templates page from the crumbtrail.

Edit a template used by no guides

1. Site Administration > Plugins > Blocks > Moderator guide > Manage templates
2. Admin clicks on "Edit" on the HLS template.
3. Admin can edit all fields, including the HTML editor containing the placeholders.
4. Admin press "Save changes"

Edit a template used by at least a guide

1. Site Administration > Plugins > Blocks > Moderator guide > Manage templates
2. Admin clicks on "Edit" on a template used by a guide (you should see a "Guides" link).
3. Admin can edit all fields, except the HTML editor containing the placeholders. 
However the HTML editor content is displayed (the admin see the placeholder code)
4. Admin press "Save changes"

Edits an existing template

1. admin go to manage template page and look for the template he wants to edit in the existing template list. Only not used template can be edited.
2. admin clicks on edit
3. the admin can edit everything.
4. if the admin selects a different template then the plugin will try to prefill the placeholder with the previous placeholder value (whenever a placeholder hold the same position and type).
4. once the admin presses saves.

Hides/shows a template

1. admin goes to manage template page and look for the template he wants to hide in the existing template list.
2. admin clicks on hide icon
3. the template is marked as hidden and it cannot be selected by the main teacher. The guides using this template are still visible and editable. 

Deletes a template with at least one guide.

1. Site Administration > Plugins > Blocks > Moderator guide > Manage templates
2. Admin clicks on "Delete" on the HLS template.
3. A new page display the list of the guides using this template and a message saying they are going to be deleted.
4. User can access each guide page from listed guides.
5. Admin clicks on "Delete"
6. The templates and all guides are deleted.


**Guide creator / Main teacher**

* Add a new guide for a course
* Edit an existing guide for a course
* Delete an existing guide for a course

**External teacher / External reviewer**

* View an existing guide for a course

**Student**

By default they don't have the permission to see guides 
and so they should not see the block.


[![Demo video](https://img.youtube.com/vi/jISpfobm8H4/0.jpg)](https://www.youtube.com/watch?v=jISpfobm8H4)