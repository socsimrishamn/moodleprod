ANALYSIS FOR THE DEVELOPMENT OF COURSE GUIDE
============================================

This document provides a course of action and advice for designing the new Moodle plugin "Course Guide" based on the existing plugin "Moderator Guide" which includes a lot of similar features.

This approach taken in this document is to make the API as simple as possible.

General
-------

The code of the block will have to be refactored to use classes. Thoses classes will be the base for most plugins which want to use the "guide" features.

Data structure
--------------

Any plugin which decides to use the guide system has to re-define the same tables.
However, note that the minimum fields required for the templates table are:

* id (int)
* name (char)
* organization (char)
* description (text)
* template (text)
* templateformat (int)
* timecreated (int)
* timemodified (int)
* hidden (int 1)

Any additional field, such as 'defaultguidename' in the case of the block plugin, are considered to be plugin specific options.

The minimum fields required for the guides table are:

* id (int)
* templateid (int)
* creatorid (int)
* timecreated (int)
* timemodified (int)
* hidden (int)

Additional columns such as 'name' or 'courseid' are specific to the component.

Template manager base
---------------------

Each plugin needs to extend this.

```php
abstract class template_manager_base {

    // Return the name of the appropriate tables;
    abstract public function get_templates_table();
    abstract public function get_guides_table();
    abstract public function get_guide_contents_table();

    // Return the template class to use.
    abstract public function get_template_class();

    // Return the guide class to use.
    // - Defaults to the standard guide class.
    public function get_guide_class();

    // Return a template.
    public function get($templateid);

    // Returns all the templates.
    public function list();

    // Find the templates which are visible and match the current user's organisation.
    public function list_visible();

    // Return whether the current user has visible templates.
    // - To replace 'block_moderator_guide_template_exists'.
    public function has_visible();

    // Save (update or create) a template.
    public function save(template_base $template);

    // Delete a template.
    // - To replace block_moderator_guide_delete_template.
    public function delete($templateid);

    // Return a guide.
    // - This also loads the guide's template at the same time.
    public function get_guide($guideid);

    // Returns a new guide instance.
    // - This is useful when we want to preview a template and thus generate a dummy
    //   guide, or when we want to start a new guide.
    public function get_new_guide(template_base $template);

    // Return the form instance to create/edit a guide from this template.
    // - The guide is used as default data.
    // - This prepares the file area.
    public function get_guide_form($actionurl, guide $guide);

    // Creates or updates the guide with the form data passed.
    // - This also deals with the file API.
    // - This must be coded inline with the form from get_guide_form.
    public function save_guide_from_form_data(guide $guide, $formdata);

    // Delete a guide.
    // - To replace block_moderator_guide_delete_guide.
    public function delete_guide($guideid);

    // Encapsulates all the logic for rendering the guide.
    public function render(guide $guide);
}
```

Template base
-------------

Each plugin needs to extend this.

```php
abstract class template_base {

    // The template can be initialised with a database record.
    public function __construct(stdClass $record = null);

    // Return an stdClass including all of the properties of the object, including its ID if any.
    public function get_record();

    // Return whether the current user can view the template.
    // - To replace block_moderator_guide_can_see_template.
    public function can_view();

    // Throws an exception when can_view() is false.
    // - To replace block_moderator_guide_can_see_template.
    public function require_view();

    // Return parse info from that template.
    public function parse();

}
```


Guide
-----

Plugins need to extend this if they have additional fields in the database.
It's likely to be the case for both course and block guides.

```php
class guide {

    // The guide needs to know what template it is from.
    public function construct(template_base $template, stdClass $record = null, array $contents = []);

    // The visible name for a user to see.
    // - Use this to be able to swap the name for something else later.
    // - The default implementation (block implementation) will return the name value from the record.
    // - The module implementation will return the name of the activity. e.g.:
    //   context_module::instance_by_id($this->record->cmid)->get_context_name();
    public function get_name();

    // Whether the guide can be seen, probably only defers that to $this->template->require_view();
    // - To replace block_moderator_guide_require_organisation_by_guide.
    public function require_view();

    // Return the guide record for submission to the database, including its ID if any.
    public function get_record();

    // Return the guide's contents, including the IDs of the existing entries if any.
    public function get_contents();

}
```

Guide content
-------------

This is simply a class which allows a better verbosity of the fields its meant to contain.
A simple stdClass can be used instead.

This class does not need to be extended by plugins.

```php
class guide_content {

    public $id;
    public $guideid;
    public $value;
    public $valueformat;
    public $placeholderid;
    public $placeholdertype;
    public $timecreated;
    public $timemodified;

}
```