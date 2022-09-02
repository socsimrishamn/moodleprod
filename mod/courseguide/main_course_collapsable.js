// This file is part of Moderator Guide plugin for Moodle
//
// Moderator Guide plugin for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moderator Guide plugin for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moderator Guide plugin for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * edit_template.php JS script.
 *
 * @package    mod_courseguide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */

// hide all guides.
$('.mod_courseguide_collapsable_content').css('display', 'none');

var mod_courseguide_expand_callback = function(element) {

    var content = element.next();
    var icon = element;

    if (content.css("display") === 'none') {
        content.css("display", 'block');

        icon.text("▼");

    } else {
        content.css("display", 'none');

        icon.text("◀");
    }
};

var mod_courseguide_expand_callback_this = function(event) {

    mod_courseguide_expand_callback($(this));

    // Forbid the otherthing to happen (like triggering a second click on the element,
    // as the element attr changed with pushy adding classes to the element)
    event.stopPropagation();
    event.preventDefault();
}

$('.mod_courseguide_expandicon').click(
    mod_courseguide_expand_callback_this
);