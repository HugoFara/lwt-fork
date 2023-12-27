/**
 * All the function to make an audio controller in do_text_header.php
 * 
 * @license Unlicense
 */


/*
 * An audio controller. 
 */
const lwt_audio_controller = {
    /**
     * Change the position of the audio player head.
     * 
     * @param {Number} position New player head
     */
    newPosition: function (position) {
        $("#jquery_jplayer_1").jPlayer("playHead", position);
    },

    setNewPlayerseconds: function () {
        const newval = $("#backtime :selected").val();
        do_ajax_save_setting('currentplayerseconds', newval);
    },

    setNewPlaybackrate: function () {
        const newval = $("#playbackrate :selected").val();
        do_ajax_save_setting('currentplaybackrate', newval); 
        $("#jquery_jplayer_1").jPlayer("option","playbackRate", newval * 0.1);
    },

    setCurrentPlaybackrate: function () {
        const val = $("#playbackrate :selected").val();
        $("#jquery_jplayer_1").jPlayer("option","playbackRate", val * 0.1);
    },

    clickSingle: function () {
        $("#jquery_jplayer_1").off('bind', $.jPlayer.event.ended + ".jp-repeat");
        $("#do-single").addClass('hide');
        $("#do-repeat").removeClass('hide');
        do_ajax_save_setting('currentplayerrepeatmode','0');
        return false;
    },

    clickRepeat: function () {
        $("#jquery_jplayer_1")
        .on('bind', $.jPlayer.event.ended + ".jp-repeat", function(_event) { 
            $(this).jPlayer("play"); 
        });
        $("#do-repeat").addClass('hide');
        $("#do-single").removeClass('hide');
        do_ajax_save_setting('currentplayerrepeatmode','1');
        return false;
    },

    clickBack: function () {
        const t = parseInt($("#playTime").text(), 10);
        const b = parseInt($("#backtime").val(), 10);
        let nt = t - b;
        let st = 'pause';
        if (nt < 0) 
            nt = 0;
        if (!$('#jquery_jplayer_1').data().jPlayer.status.paused)
            st = 'play';
        $("#jquery_jplayer_1").jPlayer(st, nt);
    },

    clickForward: function () {
        const t = parseInt($("#playTime").text(), 10);
        const b = parseInt($("#backtime").val(), 10);
        const nt = t + b;
        let st = 'pause';
        if (!$('#jquery_jplayer_1').data().jPlayer.status.paused)
            st = 'play';
        $("#jquery_jplayer_1").jPlayer(st, nt);
    },

    clickSlower: function () {
        const val = parseFloat($("#pbvalue").text()) - 0.1;
        if (val >= 0.5) {
            $("#pbvalue").text(val.toFixed(1)).css({'color': '#BBB'})
            .animate({color: '#888'},150,function() {});
            $("#jquery_jplayer_1").jPlayer("playbackRate", val);
        }
    },

    clickFaster: function () {
        const val = parseFloat($("#pbvalue").text()) + 0.1;
        if (val <= 4.0){
            $("#pbvalue").text(val.toFixed(1)).css({'color': '#BBB'})
            .animate({color: '#888'},150,function() {});
            $("#jquery_jplayer_1").jPlayer("playbackRate", val);
        }
    },

    clickStdSpeed: function () {
        $("#playbackrate").val(10);
        this.setNewPlaybackRate();
    },

    clickSlower: function () {
        let val = $("#playbackrate :selected").val();
        if (val > 5) {
            val--;
            $("#playbackrate").val(val);
            this.setNewPlaybackRate();
        }
    },

    clickFaster: function () {
        let val = $("#playbackrate :selected").val();
        if (val < 15) {
            val++;
            $("#playbackrate").val(val);
            this.setNewPlaybackRate();
        }
    },

}

/**
 * Change the position of the audio player head.
 * 
 * @param {Number} p New player head
 * 
 * @deprecated Since LWT 2.9.1, use lwt_audio_controller.newPosition
 */
function new_pos(p) {
    return lwt_audio_controller.newPosition(p);
}

function set_new_playerseconds() {
    return lwt_audio_controller.setNewPlayerseconds();
}

function set_new_playbackrate() {
    return lwt_audio_controller.setNewPlaybackrate();
}

function set_current_playbackrate() {
    return lwt_audio_controller.setCurrentPlaybackrate();
}

function click_single() {
    return lwt_audio_controller.clickSingle();
}

function click_repeat() {
    return lwt_audio_controller.clickRepeat();
}

function click_back() {
    return lwt_audio_controller.clickBack();
}

function click_forw() {
    return lwt_audio_controller.clickForward();
}

function click_slower() {
    return lwt_audio_controller.clickSlower();
}

function click_faster() {
    return lwt_audio_controller.clickFaster();
}

function click_stdspeed() {
    return lwt_audio_controller.clickStdSpeed();
}

function click_slower() {
    return lwt_audio_controller.clickSlower();
}

function click_faster() {
    return lwt_audio_controller.clickFaster();
}
