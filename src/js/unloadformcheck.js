/**
 * Check for unsaved changes when unloading window.
 * 
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 * @since   2.3.1-fork You should not only include this script to check before unload
 * 					but also call ask_before_exiting once.
 * @since   2.10.0-fork This file was refactored in a single object, use it instead
 */

/**
 * Set to 1 if a form was altered (set "dirty"), 
 * ask for confirmation before leaving.
 * 
 * @deprecated Since 2.10.0, use lwt_form_check instead
 */
var DIRTY = 0;

/**
 * Keeps track of a modified form.
 */
const lwt_form_check = {

	dirty: false,

	/**
	 * Check the DIRTY status and ask before leaving.
	 * 
	 * @returns {string} Confirmation string
	 */
	isDirtyMessage: function() {
	   if (lwt_form_check.dirty) {
		  return '** You have unsaved changes! **';
	   }
	},
 
	/**
	 * Set the DIRTY variable to 1.
	 */
	makeDirty: function() {
		lwt_form_check.dirty = true;
	},
 
	/**
	 * Set the DIRTY variable to 0.
	 */
	resetDirty: function() {
		lwt_form_check.dirty = false;
	},
 
	/**
	 * Set DIRTY to 1 if tag object changed.
	 * 
	 * @param {*}      _  An event, unused
	 * @param {object} ui UI object
	 * @returns {true} Always return true
	 */
	tagChanged: function(_, ui) {
	   if (!ui.duringInitialization) {
		lwt_form_check.dirty = true;
	   }
	   return true;
	},
 
	/**
	 * Call this function if you want to ask the user 
	 * before exiting the form.
	 * 
	 * @returns {undefined}
	 */
	askBeforeExit: function() {
	   $('#termtags').tagit({
		  afterTagAdded: lwt_form_check.tagChanged, 
		  afterTagRemoved: lwt_form_check.tagChanged
	   });
	   $('#texttags').tagit({
		  afterTagAdded: lwt_form_check.tagChanged, 
		  afterTagRemoved: lwt_form_check.tagChanged
	   }); 
	   $('input,checkbox,textarea,radio,select')
		  .not('#quickmenu').on('change', lwt_form_check.makeDirty);
	   $(':reset,:submit').on('click', lwt_form_check.resetDirty);
	   $(window).on('beforeunload', lwt_form_check.isDirtyMessage);
	}
 };
 
/**
 * Check the DIRTY status and ask before leaving.
 * 
 * @returns {string} Confirmation string
 * @deprecated Since 2.10.0, use return lwt_form_check.isDirtyMessage instead
 */
function askConfirmIfDirty() {
    return lwt_form_check.askConfirmIfDirty();
}

/**
 * Set the DIRTY variable to 1.
 * @deprecated Since 2.10.0, use return lwt_form_check.makeDirty instead
 */
function makeDirty() {
    return lwt_form_check.makeDirty();
}

/**
 * Set the DIRTY variable to 0.
 * @deprecated Since 2.10.0, use return lwt_form_check.resetDirty instead
 */
function resetDirty() {
    return lwt_form_check.resetDirty();
}

/**
 * Set DIRTY to 1 if tag object changed.
 * 
 * @param {*}      _  An event, unused
 * @param {object} ui UI object
 * @returns {true} Always return true
 * @deprecated Since 2.10.0, use return lwt_form_check.tagChanged instead
 */
function tagChanged(_, ui) {
    return lwt_form_check.tagChanged(_, ui);
}

/**
 * Call this function if you want to ask the user 
 * before exiting the form.
 * 
 * @returns {undefined}
 * @deprecated Since 2.10.0, use return lwt_form_check.askBeforeExit instead
 */
function ask_before_exiting() {
    return lwt_form_check.askBeforeExiting();
} 
