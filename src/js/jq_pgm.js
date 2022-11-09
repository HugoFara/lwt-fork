/**
 * \file
 * \brief Interaction between LWT and jQuery
 * 
 * @package Lwt
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

/**************************************************************
Global variables used in LWT jQuery functions
***************************************************************/

var TEXTPOS = -1;
var OPENED = 0;
/** @var {int} WID - Word ID */
var WID = 0;
/** Text ID (int) */
var TID = 0;
/** First dictionary URL */
var WBLINK1 = '';
/** Second dictionary URL */
var WBLINK2 = '';
/** Google Translate */
var WBLINK3 = '';
var SOLUTION = '';
var ADDFILTER = '';
/** Right-to-left indicator */
var RTL = 0;
var ANN_ARRAY = {};
var DELIMITER = '';
var JQ_TOOLTIP = 0;

/**************************************************************
LWT jQuery functions
***************************************************************/

function setTransRoman (tra, rom) {
  if ($('textarea[name="WoTranslation"]').length == 1) { 
    $('textarea[name="WoTranslation"]').val(tra); 
  }
  if ($('input[name="WoRomanization"]').length == 1) { 
    $('input[name="WoRomanization"]').val(rom); 
  }
  makeDirty();
}

function containsCharacterOutsideBasicMultilingualPlane (s) {
  return /[\uD800-\uDFFF]/.test(s);
}

function alertFirstCharacterOutsideBasicMultilingualPlane (s, info) {
  const match = /[\uD800-\uDFFF]/.exec(s);
  if (match) {
    alert('ERROR\n\nText "' + info + '" contains invalid character(s) (in the Unicode Supplementary Multilingual Planes, > U+FFFF) like emojis or very rare characters.\n\nFirst invalid character: "' + s.substring(match.index, match.index + 2) + '" at position ' + (match.index + 1) + '.\n\nMore info: https://en.wikipedia.org/wiki/Plane_(Unicode)\n\nPlease remove this/these character(s) and try again.');
    return 1;
  } else {
    return 0;
  }
}

function getUTF8Length (s) {
  return (new Blob([String(s)]).size);
}

function scrollToAnchor (aid) {
  document.location.href = '#' + aid;
}

function changeImprAnnText () {
  const textid = $('#editimprtextdata').attr('data_id');
  $(this).prev('input:radio').attr('checked', 'checked');
  const elem = $(this).attr('name');
  const idwait = '#wait' + elem.substring(2);
  $(idwait).html('<img src="icn/waiting2.gif" />');
  const thedata = JSON.stringify($('form').serializeObject());
  $.post('inc/ajax_save_impr_text.php', { id: textid, elem: elem, data: thedata }
    , function (d) {
      $(idwait).html('<img src="icn/empty.gif" />');
      if (d != 'OK') { alert('Saving your changes failed, please reload page and try again!'); }
    }
  );
}

function changeImprAnnRadio () {
  const textid = $('#editimprtextdata').attr('data_id');
  const elem = $(this).attr('name');
  const idwait = '#wait' + elem.substring(2);
  $(idwait).html('<img src="icn/waiting2.gif" />');
  const thedata = JSON.stringify($('form').serializeObject());
  $.post('inc/ajax_save_impr_text.php', { id: textid, elem: elem, data: thedata }
    , function (d) {
      $(idwait).html('<img src="icn/empty.gif" />');
      if (d != 'OK') { alert('Saving your changes failed, please reload page and try again!'); }
    }
  );
}

function addTermTranslation (wordid, txid, word, lang) {
  const thedata = $(txid).val().trim();
  const pagepos = $(document).scrollTop();
  if ((thedata == '') || (thedata == '*')) {
    alert('Text Field is empty or = \'*\'!');
    return;
  }
  $.post('inc/ajax_add_term_transl.php', { id: wordid, data: thedata, text: word, lang: lang }
    , function (d) {
      if (d == '') {
        alert('Adding translation to term OR term creation failed, please reload page and try again!');
      } else {
        do_ajax_edit_impr_text(pagepos, d);
      }
    }
  );
}

/**
 * Set a new status for a word in the test table.
 * 
 * @param {string} wordid Word ID
 * @param {bool}   up     true if status sould be increased, false otherwise
 */
function changeTableTestStatus (wordid, up) {
  $.post(
    'inc/ajax_chg_term_status.php', 
    { id: wordid, data: (up ? 1 : 0) }, 
    function (data) {
      if (data != '') {
        $('#STAT' + wordid).html(data);
      }
    }
  );
}

/**
 * Check if there is no problem with the text.
 * 
 * @returns {boolean} true if all checks were successfull
 */
function check () {
  let count = 0;
  $('.notempty').each(function (_n) {
    if ($(this).val().trim() == '') count++;
  });
  if (count > 0) {
    alert('ERROR\n\n' + count + ' field(s) - marked with * - must not be empty!');
    return false;
  }
  count = 0;
  $('input.checkurl').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (($(this).val().trim().indexOf('http://') != 0) && ($(this).val().trim().indexOf('https://') != 0) && ($(this).val().trim().indexOf('#') != 0)) {
        alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must start with "http://" or "https://" if not empty.');
        count++;
      }
    }
  });
  $('input.checkregexp').each(function (_n) {
    const regexp = $(this).val().trim();
    if (regexp.length > 0) {
      $.ajax({
        type: 'POST',
        url: 'inc/ajax_check_regexp.php',
        data: { regex: regexp },
			 async: false
      }
      ).always(function (data) {
        if (data != '') {
          alert(data);
          count++;
        }
      });
    }
  });
  // To enable limits of custom feed texts/articl.
  // change the following «input[class*="max_int_"]» into «input[class*="maxint_"]»
  $('input[class*="max_int_"]').each(function (_n) {
    const maxvalue = parseInt($(this).attr('class').replace(/.*maxint_([0-9]+).*/, '$1'));
    if ($(this).val().trim().length > 0) {
      if ($(this).val() > maxvalue) {
        alert('ERROR\n\n Max Value of Field "' + $(this).attr('data_info') + '" is ' + maxvalue);
        count++;
      }
    }
  });
  $('input.checkdicturl').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (($(this).val().trim().indexOf('http://') != 0) && ($(this).val().trim().indexOf('https://') != 0) && ($(this).val().trim().indexOf('*http://') != 0) && ($(this).val().trim().indexOf('*https://') != 0) && ($(this).val().trim().indexOf('glosbe_api.php') != 0) && ($(this).val().trim().indexOf('ggl.php') != 0)) {
        alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must start with "http://" or "https://" or "*http://" or "*https://" or "glosbe_api.php" or "ggl.php" if not empty.');
        count++;
      }
    }
  });
  $('input.posintnumber').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (!(isInt($(this).val().trim()) && (parseInt($(this).val().trim(), 10) > 0))) {
        alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must be an integer number > 0.');
        count++;
      }
    }
  });
  $('input.zeroposintnumber').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (!(isInt($(this).val().trim()) && (parseInt($(this).val().trim(), 10) >= 0))) {
        alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must be an integer number >= 0.');
        count++;
      }
    }
  });
  $('input.checkoutsidebmp').each(function (_n) {
    if ($(this).val().trim().length > 0) {
      if (containsCharacterOutsideBasicMultilingualPlane($(this).val())) {
        count += alertFirstCharacterOutsideBasicMultilingualPlane($(this).val(), $(this).attr('data_info'));
      }
    }
  });
  $('textarea.checklength').each(function (_n) {
    if ($(this).val().trim().length > (0 + $(this).attr('data_maxlength'))) {
      alert('ERROR\n\nText is too long in field "' + $(this).attr('data_info') + '", please make it shorter! (Maximum length: ' + $(this).attr('data_maxlength') + ' char.)');
      count++;
    }
  });
  $('textarea.checkoutsidebmp').each(function (_n) {
    if (containsCharacterOutsideBasicMultilingualPlane($(this).val())) {
      count += alertFirstCharacterOutsideBasicMultilingualPlane($(this).val(), $(this).attr('data_info'));
    }
  });
  $('textarea.checkbytes').each(function (_n) {
    if (getUTF8Length($(this).val().trim()) > (0 + $(this).attr('data_maxlength'))) {
      alert('ERROR\n\nText is too long in field "' + $(this).attr('data_info') + '", please make it shorter! (Maximum length: ' + $(this).attr('data_maxlength') + ' bytes.)');
      count++;
    }
  });
  $('input.noblanksnocomma').each(function (_n) {
    if ($(this).val().indexOf(' ') > 0 || $(this).val().indexOf(',') > 0) {
      alert('ERROR\n\nNo spaces or commas allowed in field "' + $(this).attr('data_info') + '", please remove!');
      count++;
    }
  });
  return (count == 0);
}

function isInt (value) {
  for (let i = 0; i < value.length; i++) {
    if ((value.charAt(i) < '0') || (value.charAt(i) > '9')) {
      return false;
    }
  }
  return true;
}

function markClick () {
  if ($('input.markcheck:checked').length > 0) {
    $('#markaction').removeAttr('disabled');
  } else {
    $('#markaction').attr('disabled', 'disabled');
  }
}

function confirmDelete () {
  return confirm('CONFIRM\n\nAre you sure you want to delete?');
}

/**
 * Enable/disable words hint. 
 * Function called when clicking on "Show All".
 */
function showAllwordsClick () {
  const showAll = $('#showallwords').prop('checked') ? '1' : '0';
  const showLeaning = $('#showlearningtranslations').prop('checked') ? '1' : '0';
  const text = $('#thetextid').text();
  // Timeout necessary because the button is clicked on the left (would hide frames)
	setTimeout(function () {
    showRightFrames(
      'set_text_mode.php?mode=' + showAll + '&showLearning=' + showLeaning + '&text=' + text
  );}, 500);
  setTimeout(function () {window.location.reload();}, 4000);
}

function textareaKeydown (event) {
  if (event.keyCode && event.keyCode == '13') {
    if (check()) 
      $('input:submit').last().trigger('click');
    return false;
  } else {
    return true;
  }
}

function noShowAfter3Secs () {
  $('#hide3').slideUp();
}

/**
 * Set the focus on an element with the "focus" class.
 * 
 * @returns undefined
 */
function setTheFocus () {
  $('.setfocus')
  .trigger('focus')
  .trigger('select');
}

/**
 * Prepare a dialog when the user clicks a word during a test.
 * 
 * @returns false
 */
function word_click_event_do_test_test () {
  run_overlib_test(
    WBLINK1, WBLINK2, WBLINK3,
    $(this).attr('data_wid'),
    $(this).attr('data_text'),
    $(this).attr('data_trans'),
    $(this).attr('data_rom'),
    $(this).attr('data_status'),
    $(this).attr('data_sent'),
    $(this).attr('data_todo')
  );
  $('.todo').text(SOLUTION);
  return false;
}

/**
 * Handle keyboard interaction when testing a word.
 * 
 * @param {object} e A keystroke object 
 * @returns {bool} true if nothing was done, false otherwise
 */
function keydown_event_do_test_test (e) {
  if (e.which == 32 && OPENED == 0) { // space : show sol.
    $('.word').trigger('click');
    cClick();
    showRightFrames('show_word.php?wid=' + $('.word').attr('data_wid') + '&ann=');
    OPENED = 1;
    return false;
  }
  if (OPENED == 0) return true;
  if (e.which == 38) { // up : status+1
		showRightFrames('set_test_status.php?wid=' + WID + '&stchange=1');
    return false;
  }
  if (e.which == 40) { // down : status-1
		showRightFrames('set_test_status.php?wid=' + WID + '&stchange=-1');
    return false;
  }
  if (e.which == 27) { // esc : dont change status
		showRightFrames('set_test_status.php?wid=' + WID + '&status=' + $('.word').attr('data_status'));
    return false;
  }
  for (let i = 1; i <= 5; i++) {
    if (e.which == (48 + i) || e.which == (96 + i)) { // 1,.. : status=i
			showRightFrames('set_test_status.php?wid=' + WID + '&status=' + i);
      return false;
    }
  }
  if (e.which == 73) { // I : status=98
		showRightFrames('set_test_status.php?wid=' + WID + '&status=98');
    return false;
  }
  if (e.which == 87) { // W : status=99
		showRightFrames('set_test_status.php?wid=' + WID + '&status=99');
    return false;
  }
  if (e.which == 69) { // E : EDIT
		showRightFrames('edit_tword.php?wid=' + WID);
    return false;
  }
  return true;
}

/**
 * Add annotations to a word.
 * 
 * @param {*} _ Unused, usually word number 
 */
function word_each_do_text_text(_) {
  const wid = $(this).attr('data_wid');
  if (wid != '') {
    const order = $(this).attr('data_order');
    if (order in ANN_ARRAY) {
      if (wid == ANN_ARRAY[order][1]) {
        const ann = ANN_ARRAY[order][2];
        const re = new RegExp('([' + DELIMITER + '][ ]{0,1}|^)(' + ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' + DELIMITER + '])', '');
        if (!re.test($(this).attr('data_trans').replace(/ \[.*$/, ''))) {
          const trans = ann + ' / ' + $(this).attr('data_trans');
          $(this).attr('data_trans', trans.replace(' / *', ''));
        }
        $(this).attr('data_ann', ann);
      }
    }
  }
  if (!JQ_TOOLTIP) {
    this.title = make_tooltip(
      $(this).text(), $(this).attr('data_trans'), $(this).attr('data_rom'), $(this).attr('data_status')
    );
  }
}

function mword_each_do_text_text(_) {
  if ($(this).attr('data_status') != '') {
    const wid = $(this).attr('data_wid');
    if (wid != '') {
      const order = parseInt($(this).attr('data_order'));
      for (let j = 2; j <= 16; j = j + 2) {
        const index = (order + j).toString();
        if (index in ANN_ARRAY) {
          if (wid == ANN_ARRAY[index][1]) {
            const ann = ANN_ARRAY[index][2];
            const re = new RegExp('([' + DELIMITER + '][ ]{0,1}|^)(' + ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' + DELIMITER + '])', '');
            if (!re.test($(this).attr('data_trans').replace(/ \[.*$/, ''))) {
              const trans = ann + ' / ' + $(this).attr('data_trans');
              $(this).attr('data_trans', trans.replace(' / *', ''));
            }
            $(this).attr('data_ann', ann);
            break;
          }
        }
      }
    }
    if (!JQ_TOOLTIP) {
      this.title = make_tooltip(
        $(this).attr('data_text'),
        $(this).attr('data_trans'), $(this).attr('data_rom'),
        $(this).attr('data_status')
      );
    }
  }
}

function word_dblclick_event_do_text_text () {
  const t = parseInt($('#totalcharcount').text(), 10);
  if (t == 0) return;
  let p = 100 * ($(this).attr('data_pos') - 5) / t;
  if (p < 0) p = 0;
  if (typeof (window.parent.frames.h.new_pos) === 'function') { 
    window.parent.frames.h.new_pos(p); 
  }
}

/**
 * Do a word edition window. Usually called when the user clicks on a word.
 * 
 * @returns {bool} false
 */
function word_click_event_do_text_text () {
  const status = $(this).attr('data_status');
  let ann = '';
  if ($(this).attr('data_ann') !== undefined) { 
    ann = $(this).attr('data_ann'); 
  }

  let hints;
  if (JQ_TOOLTIP) { 
    hints = make_tooltip(
      $(this).text(), $(this).attr('data_trans'), $(this).attr('data_rom'), status
    ); 
  } else { 
    hints = $(this).attr('title'); 
  }
  const multi_words = Array(7);
  for (let i = 0; i < 7; i++) { 
    multi_words[i] = $(this).attr('data_mw' + (i + 2)); 
  }
  if (status < 1) {
    run_overlib_status_unknown(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), $(this).text(), multi_words, RTL
    );
    showRightFrames(
      'edit_word.php?tid=' + TID + '&ord=' + $(this).attr('data_order') + '&wid='
    );
  } else if (status == 99) {
    run_overlib_status_99(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), 
      $(this).text(), $(this).attr('data_wid'), multi_words, RTL, ann
    );
  } else if (status == 98) {
    run_overlib_status_98(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), 
      $(this).text(), $(this).attr('data_wid'), multi_words, RTL, ann
    );
  } else {
    run_overlib_status_1_to_5(
      WBLINK1, WBLINK2, WBLINK3, hints,
      TID, $(this).attr('data_order'), 
      $(this).text(), $(this).attr('data_wid'), status, multi_words, RTL, ann
    );
  }
  return false;
}

function mword_click_event_do_text_text () {
  const status = $(this).attr('data_status');
  if (status != '') {
    let ann = '';
    if ((typeof $(this).attr('data_ann')) !== 'undefined') { ann = $(this).attr('data_ann'); }
    run_overlib_multiword(WBLINK1, WBLINK2, WBLINK3, JQ_TOOLTIP ? make_tooltip($(this).text(), $(this).attr('data_trans'), $(this).attr('data_rom'), status) : $(this).attr('title'),
      TID, $(this).attr('data_order'), $(this).attr('data_text'),
      $(this).attr('data_wid'), status, $(this).attr('data_code'), ann);
  }
  return false;
}

function mword_drag_n_drop_select (event) {
  if (JQ_TOOLTIP)$('.ui-tooltip').remove();
  const context = $(this).parent();
  context.one('mouseup mouseout', $(this), function () {
    clearTimeout(to);
    $('.nword').removeClass('nword');
    $('.tword').removeClass('tword');
    $('.lword').removeClass('lword');
    $('.wsty', context).css('background-color', '').css('border-bottom-color', '');
    $('#pe').remove();
  });

  to = setTimeout(function () {
    let pos;
    context.off('mouseout');
    $('.wsty', context).css('background-color', 'inherit').css('border-bottom-color', 'rgba(0,0,0,0)').not('.hide,.word').each(function () {
      f = parseInt($(this).attr('data_code')) * 2 + parseInt($(this).attr('data_order')) - 1;
      h = '';
      $(this).nextUntil($('[id^="ID-' + f + '-"]', context), '[id$="-1"]').each(function () {
        l = $(this).attr('data_order');
        if (typeof l !== 'undefined') {
          h += '<span class="tword" data_order="' + l + '">' + $(this).text() + '</span>';
        } else {
          h += '<span class="nword" data_order="' + $(this).attr('id').split('-')[1] + '">' + $(this).text() + '</span>';
        }
      });
      $(this).html(h);
    });
    $('#pe').remove();
    $('body').append('<style id="pe">#' + context.attr('id') + ' .wsty:after,#' + context.attr('id') + ' .wsty:before{opacity:0}</style>');

    $('[id$="-1"]', context).not('.hide,.wsty').addClass('nword').each(function () {
      $(this).attr('data_order', $(this).attr('id').split('-')[1]);
    });
    $('.word', context).not('.hide').each(function () {
      $(this).html('<span class="tword" data_order="' + $(this).attr('data_order') + '">' + $(this).text() + '</span>');
    });
    if (event.data.annotation == 1) {
      $('.wsty', context)
      .not('.hide')
      .each(function () { 
        $(this).children('.tword').last()
        .attr('data_ann', $(this).attr('data_ann'))
        .attr('data_trans', $(this).attr('data_trans'))
        .addClass('content' + $(this).removeClass('status1 status2 status3 status4 status5 status98 status99').attr('data_status')); });
    } else if (event.data.annotation == 3) {
      $('.wsty', context)
      .not('.hide')
      .each(function () { 
        $(this).children('.tword').first()
        .attr('data_ann', $(this).attr('data_ann'))
        .attr('data_trans', $(this).attr('data_trans'))
        .addClass('content' + $(this).removeClass('status1 status2 status3 status4 status5 status98 status99').attr('data_status')); 
      });
    }
      $(context).one('mouseover', '.tword', function () {
      $('html').one('mouseup', function () {
        $('.wsty', context).each(function () { $(this).addClass('status' + $(this).attr('data_status')); });
        if (!$(this).hasClass('tword')) {
          $('span', context).removeClass('nword tword lword');
          $('.wsty', context).css('background-color', '').css('border-bottom-color', '');
          $('#pe').remove();
        }
      });
      pos = parseInt($(this).attr('data_order'));
      $('.lword', context).removeClass('lword');
      $(this).addClass('lword');
      $(context).on('mouseleave', function () {
        $('.lword', context).removeClass('lword');
      });
      $(context).one('mouseup', '.nword,.tword', function (ev) {
        if (ev.handled !== true) {
          const len = $('.lword.tword', context).length;
          if (len > 0) {
            g = $('.lword', context).first().attr('data_order');
            if (len > 1) {
              const text = $('.lword', context).map(function () { return $(this).text(); }).get().join('');
              if (text.length > 250) {
                alert('selected text is too long!!!');
              } else {
                showRightFrames('edit_mword.php?tid=' + TID + '&len=' + len + '&ord=' + g + '&txt=' + text);
              }
            } else {
              showRightFrames('edit_word.php?tid=' + TID + '&ord=' + g + '&txt=' + $('#ID-' + g + '-1').text());
            }
          }
          $('span', context).removeClass('tword nword');
          ev.handled = true;
        }
      });
    });
    $(context).hoverIntent({
      over: function () {
        $('.lword', context).removeClass('lword');
        const lpos = parseInt($(this).attr('data_order'));
        $(this).addClass('lword');
        if (lpos > pos) {
          for (var i = pos; i < lpos; i++) {
            $('.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]', context).addClass('lword');
          }
        } else {
          for (var i = pos; i > lpos; i--) {
            $('.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]', context).addClass('lword');
          }
        }
      },
      out: function () {},
      sensitivity: 18,
      selector: '.tword'
    });
  }, 300);
}

function word_hover_over () {
  if (!$('.tword')[0]) {
    const v = $(this).attr('class').replace(/.*(TERM[^ ]*)( .*)*/, '$1');
    $('.' + v).addClass('hword');
    if (JQ_TOOLTIP) {
      $(this).trigger('mouseover');
    }
  }
}

function word_hover_out () {
  $('.hword').removeClass('hword');
  if (JQ_TOOLTIP)$('.ui-helper-hidden-accessible>div[style]').remove();
}

jQuery.fn.extend({
  tooltip_wsty_content: function () {
    var re = new RegExp('([' + DELIMITER + '])(?! )', 'g');
    let title = '';
    if ($(this).hasClass('mwsty')) {
      title =  "<p><b style='font-size:120%'>" + $(this).attr('data_text') + '</b></p>';
    } else {
      title = "<p><b style='font-size:120%'>" + $(this).text() + '</b></p>';
    }
    const roman = $(this).attr('data_rom');
    let trans = $(this).attr('data_trans').replace(re, '$1 ');
    let statname = '';
    const status = parseInt($(this).attr('data_status'));
    if (status == 0)statname = 'Unknown [?]';
    else if (status < 5)statname = 'Learning [' + status + ']';
    if (status == 5)statname = 'Learned [5]';
    if (status == 98)statname = 'Ignored [Ign]';
    if (status == 99)statname = 'Well Known [WKn]';
    if (roman != '') {
      title += '<p><b>Roman.</b>: ' + roman + '</p>';
    }
    if (trans != '' && trans != '*') {
      if ($(this).attr('data_ann')) {
        const ann = $(this).attr('data_ann');
        if (ann != '' && ann != '*') {
          var re = new RegExp('(.*[' + DELIMITER + '][ ]{0,1}|^)(' + ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' + DELIMITER + '].*$| \\[.*$)', '');
          trans = trans.replace(re, '$1<span style="color:red">$2</span>$3');
        }
      }
      title += '<p><b>Transl.</b>: ' + trans + '</p>';
    }
    title += '<p><b>Status</b>: <span class="status' + status + '">' + statname + '</span></p>';
    return title;
  }
});

jQuery.fn.extend({
	 tooltip_wsty_init: function () {
		 $(this).tooltip({
		      position: { my: 'left top+10', at: 'left bottom', collision: 'flipfit' },
		      items: '.hword',
		      show: { easing: 'easeOutCirc' },
		      content: function () { return $(this).tooltip_wsty_content(); }
    });
	 }
});

function get_position_from_id (id_string) {
  if ((typeof id_string) === 'undefined') return -1;
  const arr = id_string.split('-');
  return parseInt(arr[1]) * 10 + 10 - parseInt(arr[2]);
}

function keydown_event_do_text_text (e) {
  if (e.which == 27) { // esc = reset all
    TEXTPOS = -1;
    $('span.uwordmarked').removeClass('uwordmarked');
    $('span.kwordmarked').removeClass('kwordmarked');
    cClick();
    return false;
  }

  if (e.which == 13) { // return = edit next unknown word
    $('span.uwordmarked').removeClass('uwordmarked');
    const unknownwordlist = $('span.status0.word:not(.hide):first');
    if (unknownwordlist.size() == 0) return false;
    $(window).scrollTo(unknownwordlist, { axis: 'y', offset: -150 });
    unknownwordlist.addClass('uwordmarked').trigger('click');
    cClick();
    return false;
  }

  const knownwordlist = $('span.word:not(.hide):not(.status0)' + ADDFILTER + ',span.mword:not(.hide)' + ADDFILTER);
  const l_knownwordlist = knownwordlist.size();
  // console.log(knownwordlist);
  if (l_knownwordlist == 0) return true;

  // the following only for a non-zero known words list
  if (e.which == 36) { // home : known word navigation -> first
    $('span.kwordmarked').removeClass('kwordmarked');
    TEXTPOS = 0;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann');
    }
    showRightFrames('show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann));
    return false;
  }
  if (e.which == 35) { // end : known word navigation -> last
    $('span.kwordmarked').removeClass('kwordmarked');
    TEXTPOS = l_knownwordlist - 1;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann');
    }
    showRightFrames('show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann));
    return false;
  }
  if (e.which == 37) { // left : known word navigation
    var marked = $('span.kwordmarked');
    var currid = (marked.length == 0)
      ? (100000000)
      : get_position_from_id(marked.attr('id'));
    $('span.kwordmarked').removeClass('kwordmarked');
    // console.log(currid);
    TEXTPOS = l_knownwordlist - 1;
    for (var i = l_knownwordlist - 1; i >= 0; i--) {
      var iid = get_position_from_id(knownwordlist.eq(i).attr('id'));
      // console.log(iid);
      if (iid < currid) {
        TEXTPOS = i;
        break;
      }
    }
    // TEXTPOS--;
    // if (TEXTPOS < 0) TEXTPOS = l_knownwordlist - 1;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann'); 
    }
    showRightFrames('show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann));
    return false;
  }
  if (e.which == 39 || e.which == 32) { // space /right : known word navigation
    var marked = $('span.kwordmarked');
    var currid = (marked.length == 0)
      ? (-1)
      : get_position_from_id(marked.attr('id'));
    $('span.kwordmarked').removeClass('kwordmarked');
    // console.log(currid);
    TEXTPOS = 0;
    for (var i = 0; i < l_knownwordlist; i++) {
      var iid = get_position_from_id(knownwordlist.eq(i).attr('id'));
      // console.log(iid);
      if (iid > currid) {
        TEXTPOS = i;
        break;
      }
    }
    // TEXTPOS++;
    // if (TEXTPOS >= l_knownwordlist) TEXTPOS = 0;
    curr = knownwordlist.eq(TEXTPOS);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') { 
      ann = curr.attr('data_ann'); 
    }
    showRightFrames('show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann));
    return false;
  }

  if ((!$('.kwordmarked, .uwordmarked')[0]) && $('.hword:hover')[0]) {
    curr = $('.hword:hover');
  } else {
    if (TEXTPOS < 0 || TEXTPOS >= l_knownwordlist) return true;
    curr = knownwordlist.eq(TEXTPOS);
  }
  const wid = curr.attr('data_wid');
  const ord = curr.attr('data_order');
  const stat = curr.attr('data_status');
  const txt = (curr.hasClass('mwsty')) ? curr.attr('data_text') : curr.text();
  let dict = '';

  for (var i = 1; i <= 5; i++) {
    if (e.which == (48 + i) || e.which == (96 + i)) { // 1,.. : status=i
      if (stat == '0') {
        if (i == 1) {
          /** @var {string} sl Source language */
          const sl = getLangFromDict(WBLINK3);
          const tl = WBLINK3.replace(/.*[?&]tl=([a-zA-Z\-]*)(&.*)*$/, '$1');
          if (sl != WBLINK3 && tl != WBLINK3)
            i = i + '&sl=' + sl + '&tl=' + tl;
        }
        showRightFrames('set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=' + i);
      } else {
				showRightFrames('set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + '&status=' + i);
        return false;
      }
    }
  }
  if (e.which == 73) { // I : status=98
    if (stat == '0') {
			showRightFrames('set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=98');
    } else {
			showRightFrames('set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + '&status=98');
      return false;
    }
  }
  if (e.which == 87) { // W : status=99
    if (stat == '0') {
			showRightFrames('set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=99');
    } else {
			showRightFrames('set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + '&status=99');
    }
    return false;
  }
  if (e.which == 80) { // P : pronounce term
    const lg = getLangFromDict(WBLINK3);
    readTextAloud(txt, lg);
    return false;
  }
  if (e.which == 84) { // T : translate sentence
    if ((WBLINK3.substr(0, 8) == '*http://') || (WBLINK3.substr(0, 9) == '*https://')) {
      owin('trans.php?x=1&i=' + ord + '&t=' + TID);
    } else if ((WBLINK3.substr(0, 7) == 'http://') || (WBLINK3.substr(0, 8) == 'https://') || (WBLINK3.substr(0, 7) == 'ggl.php')) {
      showRightFrames(undefined, 'trans.php?x=1&i=' + ord + '&t=' + TID);
    }
    return false;
  }
  if (e.which == 65) { // A : set audio pos.
    let p = curr.attr('data_pos');
    const t = parseInt($('#totalcharcount').text(), 10);
    if (t == 0) return true;
    p = 100 * (p - 5) / t;
    if (p < 0) p = 0;
    if (typeof (window.parent.frames.h.new_pos) === 'function') { 
      window.parent.frames.h.new_pos(p); 
    } else { 
      return true; 
    }
    return false;
  }
  if (e.which == 71) { //  G : edit term and open GTr
    dict = '&nodict';
    setTimeout(function () {
      if ((WBLINK3.substr(0, 8) == '*http://') || (WBLINK3.substr(0, 9) == '*https://')) { 
        owin(createTheDictUrl(WBLINK3.replace('*', ''), txt)); 
      } else {
          showRightFrames(undefined, createTheDictUrl(WBLINK3, txt));
      }
    }, 10);
  }
  if (e.which == 69 || e.which == 71) { //  E / G: edit term
    let url = '';
    if (curr.hasClass('mword')) {
      url = 'edit_mword.php?wid=' + wid + '&len=' + curr.attr('data_code') + '&tid=' + TID + '&ord=' + ord + dict;
    } else if (stat == '0') {
			url =	'edit_word.php?wid=&tid=' + TID + '&ord=' + ord + dict;
    } else {
			url =	'edit_word.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + dict;
    }
    showRightFrames(url);
    return false;
  }
  return true;
}

function do_ajax_save_setting (k, v) {
  $.post('inc/ajax_save_setting.php', { k: k, v: v });
}

function do_ajax_update_media_select () {
  $('#mediaselect').html('&nbsp; <img src="icn/waiting2.gif" />');
  $.post('inc/ajax_update_media_select.php',
    function (data) { $('#mediaselect').html(data); }
  );
}

/**
 * Show the sentences containing specific word.
 * 
 * @param {int}    lang Language ID 
 * @param {string} word Term text (the looked for term) 
 * @param {string} ctl  Path of the textarea showing sentence of the edited term
 * @param {int}    woid Term id (word or multi-word)
 * @returns {undefined}
 */
function do_ajax_show_sentences (lang, word, ctl, woid) {
  $('#exsent').html('<img src="icn/waiting2.gif" />');
  $.post(
    'inc/ajax_show_sentences.php', 
    { lang: lang, word: word, ctl: ctl, woid: woid },
    function (data) { $('#exsent').html(data); }
  );
}

function do_ajax_show_similar_terms () {
  $('#simwords').html('<img src="icn/waiting2.gif" />');
  $.post(
    'inc/ajax_show_similar_terms.php', 
    { lang: $('#langfield').val(), word: $('#wordfield').val() },
    function (data) { $('#simwords').html(data); }
  );
}

/**
 * Update WORDCOUNTS in with an AJAX request.
 * 
 * @returns {undefined}
 */
function do_ajax_word_counts () {
  const t = $('.markcheck').map(function () { 
    return $(this).val(); })
    .get().join(',');
  $.post(
    'inc/ajax_word_counts.php', 
    { id: t },
    function (data) {
      WORDCOUNTS = data;
      word_count_click();
      $('.barchart').removeClass('hide');
    }, 
    'json'
    );
}

/**
 * Set a unique item in barchart to reflect how many words are known.
 * 
 * @returns {undefined}
 */
function set_barchart_item() {
  const id = $(this).find('span').first().attr('id').split('_')[2];
  /** @var {int} v Number of terms in the text */
  let v;
  if (SUW & 16) {
    v = parseInt(WORDCOUNTS.expru[id] || 0, 10) + parseInt(WORDCOUNTS.totalu[id], 10);
  } else {
    v = parseInt(WORDCOUNTS.expr[id] || 0, 10) + parseInt(WORDCOUNTS.total[id], 10);
  }
  $(this).children('li').each(function () {
    /** {number} Word count in the category */
    let cat_word_count = parseInt($(this).children('span').text(), 10);
    /*
    Linear version
		const h = (v - $(this).children('span').text()) * 25 / v;
    */
    /* 
    Logarithmic version
    (25 / v) is vocab per pixel
    log scale so the size scaled becomes Math.log(($(this).children('span').text()))
    so the total height corresponding to text vocab after scaling should be Math.log(v)
    the proportion of column height to box height is thus (Math.log(($(this).children('span').text())) / Math.log(v))
    putting this back in pixel, we get (Math.log(($(this).children('span').text())) / Math.log(v)) * 25 should be the column height
    so (25 - (Math.log(($(this).children('span').text())) / Math.log(v)) * 25) is the intended border top size.
    */
    // Avoid to put 0 in logarithm
    cat_word_count += 1;
    v += 1;
    const h = 25 - Math.log(cat_word_count) / Math.log(v) * 25;
    $(this).css('border-top-width', h + 'px');
  });
}

/**
 * Set the number of words known in a text (in edit_texts.php main page).
 * 
 * @returns {undefined}
 */
function set_word_counts () {
  $.each(WORDCOUNTS.totalu, function (key, value) {
    let knownu = known = todo = stat0 = 0;
    const expr = WORDCOUNTS.expru[key] ? parseInt((SUW & 2) ? WORDCOUNTS.expru[key] : WORDCOUNTS.expr[key]) : 0;
    if (!WORDCOUNTS.stat[key]) {
      WORDCOUNTS.statu[key] = WORDCOUNTS.stat[key] = [];
    }
    $('#total_' + key).html((SUW & 1 ? value : WORDCOUNTS.total[key]));
    $.each(WORDCOUNTS.statu[key], function (k, v) {
      if (SUW & 8)
        $('#stat_' + k + '_' + key).html(v); 
      knownu += parseInt(v);
    });
    $.each(WORDCOUNTS.stat[key], function (k, v) {
      if (!(SUW & 8))
        $('#stat_' + k + '_' + key).html(v); 
      known += parseInt(v);
    });
    $('#saved_' + key).html(known ? ((SUW & 2 ? knownu : known) - expr + '+' + expr) : 0);
    if (SUW & 4) {
      todo = parseInt(value) + parseInt(WORDCOUNTS.expru[key] || 0) - parseInt(knownu);
    } else {
      todo = parseInt(WORDCOUNTS.total[key]) + parseInt(WORDCOUNTS.expr[key] || 0) - parseInt(known);
    }
    $('#todo_' + key).html(todo);

    // added unknown percent
    // console.log(SUW);
    if (SUW & 8) {
      unknowncount = parseInt(value) + parseInt(WORDCOUNTS.expru[key] || 0) - parseInt(knownu);
      unknownpercent = Math.round(unknowncount * 10000 / (knownu + unknowncount)) / 100;
    } else {
      unknowncount = parseInt(WORDCOUNTS.total[key]) + parseInt(WORDCOUNTS.expr[key] || 0) - parseInt(known);
      unknownpercent = Math.round(unknowncount * 10000 / (known + unknowncount)) / 100;
    }
    $('#unknownpercent_' + key).html(unknownpercent == 0 ? 0 : unknownpercent.toFixed(2));
    // end here

    if (SUW & 16) {
      stat0 = parseInt(value) + parseInt(WORDCOUNTS.expru[key] || 0) - parseInt(knownu);
    } else {
      stat0 = parseInt(WORDCOUNTS.total[key]) + parseInt(WORDCOUNTS.expr[key] || 0) - parseInt(known);
    }
    $('#stat_0_' + key).html(stat0);
  });
  $('.barchart').each(set_barchart_item);
}

/**
 * Handle the click event to switch between total and 
 * unique words count in edit_texts.php.
 * 
 * @returns {undefined}
 */
function word_count_click () {
  $('.wc_cont').children().each(function () {
    if (parseInt($(this).attr('data_wo_cnt')) == 1) {
      $(this).html('u');
    } else {
      $(this).html('t');
    }
    SUW = (parseInt($('#chart').attr('data_wo_cnt')) << 4) + 
    (parseInt($('#unknownpercent').attr('data_wo_cnt')) << 3) + 
    (parseInt($('#unknown').attr('data_wo_cnt')) << 2) + 
    (parseInt($('#saved').attr('data_wo_cnt')) << 1) + 
    (parseInt($('#total').attr('data_wo_cnt')));
    set_word_counts();
  });
}

function do_ajax_edit_impr_text (pagepos, word) {
  if (word == '') $('#editimprtextdata').html('<img src="icn/waiting2.gif" />');
  const textid = $('#editimprtextdata').attr('data_id');
  $.post('inc/ajax_edit_impr_text.php', { id: textid, word: word },
    function (data) {
      eval(data);
      $.scrollTo(pagepos);
      $('input.impr-ann-text').on('change', changeImprAnnText);
      $('input.impr-ann-radio').on('change', changeImprAnnRadio);
    }
  );
}

/**
 * Show the right frames if found, and can load an URL in those frames
 * 
 * @param {string|undefined} roUrl Upper-right frame URL to laod 
 * @param {string|undefined} ruUrl Lower-right frame URL to load
 * @returns {boolean} true if frames were found, false otherwise
 */
function showRightFrames(roUrl, ruUrl) {
  if (roUrl !== undefined) {
    top.frames.ro.location.href = roUrl;
  }
  if (ruUrl !== undefined) {
    top.frames.ru.location.href = ruUrl;
  }
  if ($('#frames-r').length) {
    $('#frames-r').animate({right: '5px'}, 100);
    return true;
  }
  return false;
}

/**
 * Hide the right frames if found.
 * 
 * @returns {boolean} true if frames were found, false otherwise
 */
function hideRightFrames() {
  if ($('#frames-r').length) {
    $('#frames-r').animate({right: '-100%'}, 100);
    return true;
  }
  return false;
}

/**
 * Play the success sound.
 *  
 * @returns {object} Promise on the status of sound
 */
function successSound() {
  document.getElementById('success_sound').pause();
  document.getElementById('failure_sound').pause();
  return document.getElementById('success_sound').play();
}

/**
 * Play the failure sound.
 *
 * @returns {object} Promise on the status of sound
 */
function failureSound() {
  document.getElementById('success_sound').pause();
  document.getElementById('failure_sound').pause();
  return document.getElementById('failure_sound').play();
}

// Present data in a handy way, for instance in a form
$.fn.serializeObject = function () {
  const o = {};
  const a = this.serializeArray();
  $.each(a, function () {
    if (o[this.name] !== undefined) {
      if (!o[this.name].push) {
        o[this.name] = [o[this.name]];
      }
      o[this.name].push(this.value || '');
    } else {
      o[this.name] = this.value || '';
    }
  });
  return o;
};

/**
 * Wrap the radio buttons into stylised elements.
 */
function wrapRadioButtons() {
  $(':input,.wrap_checkbox span,.wrap_radio span,a:not([name^=rec]),select,#mediaselect span.click,#forwbutt,#backbutt')
  .each(function (i) { $(this).attr('tabindex', i + 1); });
  $('.wrap_radio span').on('keydown', function (e) {
    if (e.keyCode == 32) {
      $(this).parent().parent().find('input[type=radio]').trigger('click');
      return false;
    }
  });
}

/**
 * Do a lot of different DOM manipulations
 */
function prepareMainAreas() {
  $('.edit_area').editable('inline_edit.php',
    {
      type: 'textarea',
      indicator: '<img src="icn/indicator.gif">',
      tooltip: 'Click to edit...',
      submit: 'Save',
      cancel: 'Cancel',
      rows: 3,
      cols: 35
    }
  );
  $('select').wrap("<label class='wrap_select'></label>");
  $('form').attr('autocomplete', 'off');
  $('input[type="file"]').each(function () {
    if (!$(this).is(':visible')) {
      $(this).before('<button class="button-file">Choose File</button>')
			 .after('<span style="position:relative" class="fakefile"></span>')
			 .on('change', function () {
          let txt = this.value.replace('C:\\fakepath\\', '');
          if (txt.length > 85)txt = txt.replace(/.*(.{80})$/, ' ... $1');
          $(this).next().text(txt);
			 })
			 .on('onmouseout', function () {
          let txt = this.value.replace('C:\\fakepath\\', '');
          if (txt.length > 85)txt = txt.replace(/.*(.{80})$/, ' ... $1');
          $(this).next().text(txt);
			 });
    }
  });
  $('input[type="checkbox"]').each(function (z) {
    if (typeof z === 'undefined')z = 1;
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'cb_' + z++);
    }
    $(this).after('<label class="wrap_checkbox" for="' + $(this).attr('id') + '"><span></span></label>');
  });
  $('span[class*="tts_"]').on('click', function () {
    const lg = $(this).attr('class').replace(/.*tts_([a-zA-Z-]+).*/, '$1');
    const txt = $(this).text();
    readRawTextAloud(txt, lg);
  });
  $(document).on('mouseup', function () {
    $('button,input[type=button],.wrap_radio span,.wrap_checkbox span')
    .trigger('blur');
  });
  $('.wrap_checkbox span').on('keydown', function (e) {
    if (e.keyCode == 32) {
      $(this).parent().parent().find('input[type=checkbox]').trigger('click');
      return false;
    }
  });
  $('input[type="radio"]').each(function (z) {
    if (z === undefined) {
      z = 1;
    }
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'rb_' + z++);
    }
    $(this).after('<label class="wrap_radio" for="' + $(this).attr('id') + '"><span></span></label>');
  });
  $('.button-file').on('click', function () { 
    $(this).next('input[type="file"]').trigger('click'); 
    return false; 
  });
  $('input.impr-ann-text').on('change', changeImprAnnText);
  $('input.impr-ann-radio').on('change', changeImprAnnRadio);
  $('form.validate').on('submit', check);
  $('input.markcheck').on('click', markClick);
  $('.confirmdelete').on('click', confirmDelete);
  $('textarea.textarea-noreturn').on('keydown', textareaKeydown);
  $('#termtags').tagit(
    {
      beforeTagAdded: function (_event, ui) {
        return !containsCharacterOutsideBasicMultilingualPlane(ui.tag.text());
      },
      availableTags: TAGS,
      fieldName: 'TermTags[TagList][]'
    }
  );
  $('#texttags').tagit(
    {
      beforeTagAdded: function (_event, ui) {
        return !containsCharacterOutsideBasicMultilingualPlane(ui.tag.text());
      },
      availableTags: TEXTTAGS,
      fieldName: 'TextTags[TagList][]'
    }
  );
  markClick();
  setTheFocus();
  if ($('#simwords').length > 0 && $('#langfield').length > 0 && $('#wordfield').length > 0) {
  	$('#wordfield').on('blur', do_ajax_show_similar_terms);
  	do_ajax_show_similar_terms();
  }
  window.setTimeout(noShowAfter3Secs, 3000);
}

$(window).on('load', wrapRadioButtons);

$(document).ready(prepareMainAreas);
