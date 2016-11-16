define(['jquery', 'jqueryui'], function ($, ui) {

    return {
        init: function () {

            /**
             * Created by Dmitriy on 08/07/14.
             */
            $(document).ready(function () {

                !function (e, t) {
                    var i;
                    return i = function () {
                        function t(t, i, n) {
                            var r, s;
                            r = e(t), s = {
                                root: "/",
                                script: "/files/filetree",
                                folderEvent: "click",
                                expandSpeed: 500,
                                collapseSpeed: 500,
                                expandEasing: "swing",
                                collapseEasing: "swing",
                                multiFolder: !0,
                                loadMessage: "Loading...",
                                errorMessage: "Unable to get file tree information",
                                multiSelect: !1,
                                onlyFolders: !1,
                                onlyFiles: !1
                            }, this.jqft = {container: r}, this.options = e.extend(s, i), this.callback = n, r.html('<ul class="jqueryFileTree start"><li class="wait">' + this.options.loadMessage + "<li></ul>"), this.showTree(r, escape(this.options.root))
                        }

                        return t.prototype.showTree = function (t, i) {
                            var n, r, s, a;
                            return n = e(t), a = this.options, r = this, n.addClass("wait"), e(".jqueryFileTree.start").remove(), s = {
                                dir: i,
                                onlyFolders: a.onlyFolders,
                                onlyFiles: a.onlyFiles,
                                multiSelect: a.multiSelect
                            }, e.ajax({url: a.script, type: "POST", dataType: "HTML", data: s}).done(function (t) {
                                var s;
                                return n.find(".start").html(""), n.removeClass("wait").append(t), a.root === i ? n.find("UL:hidden").show("undefined" != typeof callback && null !== callback) : (void 0 === jQuery.easing[a.expandEasing] && (console.log("Easing library not loaded. Include jQueryUI or 3rd party lib."), a.expandEasing = "swing"), n.find("UL:hidden").slideDown({
                                    duration: a.expandSpeed,
                                    easing: a.expandEasing
                                })), s = e('[rel="' + decodeURIComponent(i) + '"]').parent(), a.multiSelect && s.children("input").is(":checked") && s.find("ul li input").each(function () {
                                    return e(this).prop("checked", !0), e(this).parent().addClass("selected")
                                }), r.bindTree(n)
                            }).fail(function () {
                                return n.find(".start").html(""), n.removeClass("wait").append("<p>" + a.errorMessage + "</p>"), !1
                            })
                        }, t.prototype.bindTree = function (t) {
                            var i, n, r, s, a, l;
                            return i = e(t), a = this.options, s = this.jqft, n = this, r = this.callback, l = /^\/.*\/$/i, i.find("LI A").on(a.folderEvent, function () {
                                var t, i;
                                return t = {}, t.li = e(this).closest("li"), t.type = null != (i = t.li.hasClass("directory")) ? i : {directory: "file"}, t.value = e(this).text(), t.rel = e(this).prop("rel"), t.container = s.container, e(this).parent().hasClass("directory") ? e(this).parent().hasClass("collapsed") ? (n._trigger(e(this), "filetreeexpand", t), a.multiFolder || (e(this).parent().parent().find("UL").slideUp({
                                    duration: a.collapseSpeed,
                                    easing: a.collapseEasing
                                }), e(this).parent().parent().find("LI.directory").removeClass("expanded").addClass("collapsed")), e(this).parent().removeClass("collapsed").addClass("expanded"), e(this).parent().find("UL").remove(), n.showTree(e(this).parent(), e(this).attr("rel").match(l)[0]), n._trigger(e(this), "filetreeexpanded", t)) : (n._trigger(e(this), "filetreecollapse", t), e(this).parent().find("UL").slideUp({
                                    duration: a.collapseSpeed,
                                    easing: a.collapseEasing
                                }), e(this).parent().removeClass("expanded").addClass("collapsed"), n._trigger(e(this), "filetreecollapsed", t)) : (a.multiSelect ? e(this).parent().find("input").is(":checked") ? (e(this).parent().find("input").prop("checked", !1), e(this).parent().removeClass("selected")) : (e(this).parent().find("input").prop("checked", !0), e(this).parent().addClass("selected")) : (s.container.find("li").removeClass("selected"), e(this).parent().addClass("selected")), n._trigger(e(this), "filetreeclicked", t), "function" == typeof r && r(e(this).attr("rel"))), !1
                            }), "click" !== a.folderEvent.toLowerCase ? i.find("LI A").on("click", function () {
                                return !1
                            }) : void 0
                        }, t.prototype._trigger = function (t, i, n) {
                            var r;
                            return r = e(t), r.trigger(i, n)
                        }, t
                    }(), e.fn.extend({
                        fileTree: function (t, n) {
                            return this.each(function () {
                                var r, s;
                                return r = e(this), s = r.data("fileTree"), s || r.data("fileTree", s = new i(this, t, n)), "string" == typeof t ? s[option].apply(s) : void 0
                            })
                        }
                    })
                }($, window);

                $('#id_url').keyup(function () {
                    var connectUrl = $(this).val();
                    delay(function () {
                        connect_get_sco_by_url(connectUrl);
                    }, 1000);
                });

                $("#id_url").blur(function () {
                    var connectUrl = $(this).val();
                    connect_get_sco_by_url(connectUrl);
                });

                $('#id_browse').click(function () {
                    var tag = $("<div id='browseurl_window'></div>");

                    tag.fileTree({
                        // root: '/some/folder/',
                        script: window.wwwroot + '/local/connect/ajax/jqueryFileTree.php',
                        expandSpeed: 1000,
                        collapseSpeed: 1000,
                        multiFolder: false
                    }, function (connectUrl) {
                        $('#browseurl_window').dialog("destroy");
                        $('#id_url').val(connectUrl);
                        connect_get_sco_by_url(connectUrl);
                    }).dialog({
                        title: window.browsetitle,
                        modal: true,
                        width: '80%',
                        minHeight: 450,
                        height: 450,
                        resizable: false
                    }).dialog('open');
                });

                var delay = (function () {
                    var timer = 0;
                    return function (callback, ms) {
                        clearTimeout(timer);
                        timer = setTimeout(callback, ms);
                    };
                })();

                hideRemindersSection();
                hidePositionGrading();
                $('#id_start_enabled').change(function () {
                    hideRemindersSection();
                    hidePositionGrading();
                });
                hideGradingFields();
                $('#id_detailgrading').change(function () {
                    hideGradingFields();
                });

                connectUrl = $('#id_url').val();
                if (connectUrl) {
                    checkIfVpRecording(connectUrl);
                }
            });

            function connect_get_sco_by_url(connectUrl) {
                if( $( "input[name='typeisvideo']" ).val() ){
                    return;
                }

                $("#id_ajax_spin").remove();
                $('#id_browse').after(' <span id="id_ajax_spin" class="rt-loading-image"></span>');
                $.ajax({
                    dataType: "json",
                    url: window.wwwroot + "/mod/rtrecording/ajax/ajax.php",
                    data: {
                        action: "connect_get_sco_by_url",
                        url: connectUrl
                    }
                }).success(function (data) {
                    if (data.refined_noauth) {
                        $('#id_url').val('');
                        $('#id_name').val('');
                        add_alert('danger', data.refined_noauth_message);
                    } else if (data.error) {
                        add_alert('danger', data.error);
                    } else if (data.response == 'connect_not_update') {
                        if (typeof M.str.connect.connect_not_update != 'undefined') {
                            var msg =
                                M.str.connect.typelistmeeting +
                                M.str.connect.connect_not_update;
                            add_alert('danger', msg);
                        }
                    } else if (data.response == 'no-data' || data.response.fixedurl ) {
                        if( data.response.fixedurl ){
                            connectUrl = data.response.fixedurl;
                        }
                        $('#id_url').val(connectUrl);
                        if (typeof M.str.connect.whensaved != 'undefined') {
                            var msg = M.str.connect.notfound +
                                M.str.connect.typelistmeeting +
                                M.str.connect.whensaved;
                            add_alert('success', msg);
                        } else if (typeof M.str.connect.connect_not_update != 'undefined') {
                            var msg = M.str.connect.notfound +
                                M.str.connect.typelistmeeting +
                                M.str.connect.connect_not_update;
                            add_alert('danger', msg);
                        }
                        $('#id_name').removeClass('do-not-check');
                    } else if (data.response.icon != 'archive' ){
                        $('#id_url').val('');
                        $('#id_name').val('');
                        add_alert( 'danger', 'Provided Url must be for a recording' );
                    } else if (data.response.name) {
                        $('#id_url').val(data.response.url.replace(/\//g,'')); // in case the url was cleaned
                        $('#id_name').val(data.response.name).addClass('do-not-check');
                        if( typeof tinymce !== 'undefined' ){
                            tinymce.get("id_introeditor").setContent(data.response.desc);
                        }else{
                            $('#id_introeditoreditable').html(data.response.desc);
                        }
                    } else if( data.response == 'denied' ){
                        $('#id_url').val('');
                        add_alert('danger', 'Access Denied, you do not have access to this url' );
                    } else {
                        add_alert('danger', data);
                    }

                    checkIfVpRecording( connectUrl );

                }).done(function () {
                    $("#id_ajax_spin").remove();
                });
            }

            function checkIfVpRecording( connectUrl ){
                // check if VP recording or not
                $.ajax({
                    dataType: "json",
                    url: window.wwwroot + "/mod/rtrecording/ajax/ajax.php",
                    data: {
                        action: "connect_check_if_vp_recording",
                        url: connectUrl
                    }
                }).success(function (data) {
                    if( data.response ){
                        $("#id_detailgrading").attr("disabled", false);
                    }else{
                        $("#id_detailgrading").val(0);
                        $("#id_detailgrading").attr("disabled", true);
                        $("#fgroup_id_tg1").hide();
                        $("#fgroup_id_tg2").hide();
                        $("#fgroup_id_tg3").hide();
                        $("#fgroup_id_tg1vp").hide();
                        $("#fgroup_id_tg2vp").hide();
                        $("#fgroup_id_tg3vp").hide();
                    }
                });
            }

            function connect_get_sco_by_name(connectName) {
                if( $( "input[name='typeisvideo']" ).val() ){
                    return;
                }
                $("#id_ajax_spin_name").remove();
                $('#id_name').after(' <span id="id_ajax_spin_name" class="rt-loading-image"></span>');
                $.ajax({
                    dataType: "json",
                    url: window.wwwroot + "/mod/rtrecording/ajax/ajax.php",
                    data: {
                        action: "connect_get_sco_by_name",
                        name: connectName
                    }
                }).success(function (data) {
                    if (data.refined_noauth) {
                        $('#id_url').val('');
                        $('#id_name').val('');
                    } else if (data.error) {
                        add_name_alert('danger', data.error);
                    } else if (data.response == 'no-data') {
                        var msg = 'The requested name is not found and can be used';
                        add_name_alert('success', msg);
                    } else {
                        var msg = 'The requested name ( ' + connectName + ' ) is found and can not be used';
                        add_name_alert('danger', msg);
                        $('#id_name').val('');
                    }
                }).done(function () {
                    $("#id_ajax_spin_name").remove();
                });
            }

            function add_alert(type, msg) {
                $("#fgroup_id_urlgrp_alert").remove();
                $('#fgroup_id_urlgrp').after(
                    '<div class="fitem" id="fgroup_id_urlgrp_alert">' +
                    '<div class="felement fstatic alert alert-' + type + ' alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
                    msg +
                    '</div>' +
                    '</div>'
                );
            }

            function add_name_alert(type, msg) {
                $("#name_alert").remove();
                $('#fitem_id_name').after(
                    '<div class="fitem" id="name_alert">' +
                    '<div class="felement fstatic alert alert-' + type + ' alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
                    msg +
                    '</div>' +
                    '</div>'
                );
            }

            $(document).ajaxComplete(function () {
                $('#browseurl_window .sco-folder').click(function () {
                    var name = $(this).data('name');
                    var scoid = $(this).data('scoid');
                    $.ajax({
                        url: window.wwwroot + "/mod/rtrecording/browseurl.php",
                        data: {
                            type: 'meeting',
                            name: name,
                            scoid: scoid
                        }
                    }).success(function (data) {
                        $('#browseurl_window').html(data).dialog();
                    });
                });
                $('#browseurl_window .sco-breadcrumb').click(function () {
                    var name = $(this).data('name');
                    var scoid = $(this).data('scoid');
                    $.ajax({
                        url: window.wwwroot + "/mod/rtrecording/browseurl.php",
                        data: {
                            type: 'meeting',
                            name: name,
                            scoid: scoid
                        }
                    }).success(function (data) {
                        $('#browseurl_window').html(data).dialog();
                    });
                });
                $('#browseurl_window .sco-choose').click(function () {
                    var name = $(this).data('name');
                    var scoid = $(this).data('scoid');
                    $.ajax({
                        url: window.wwwroot + "/mod/rtrecording/browseurl.php",
                        data: {
                            type: 'meeting',
                            name: name,
                            scoid: scoid
                        }
                    }).success(function (data) {
                        $('#browseurl_window').html(data).dialog();
                    });
                });
                $('#browseurl_window .recording-choose').click(function () {
                    var connectUrl = $(this).text();
                    $('#browseurl_window').dialog("close");
                    $('#id_url').val(connectUrl);
                    connect_get_sco_by_url(connectUrl);
                });
            });

            function hideGradingFields() {
                var value = $('#id_detailgrading').val();
                if (value == 0) {
                    $('#fgroup_id_tg1').hide();
                    $('#fgroup_id_tg2').hide();
                    $('#fgroup_id_tg3').hide();

                    $('#fgroup_id_tg1vp').hide();
                    $('#fgroup_id_tg2vp').hide();
                    $('#fgroup_id_tg3vp').hide();
                } else if (value == 1 || value == 2) {
                    $('#fgroup_id_tg1').show();
                    $('#fgroup_id_tg2').show();
                    $('#fgroup_id_tg3').show();

                    $('#fgroup_id_tg1vp').hide();
                    $('#fgroup_id_tg2vp').hide();
                    $('#fgroup_id_tg3vp').hide();
                } else if (value == 3) {
                    $('#fgroup_id_tg1').hide();
                    $('#fgroup_id_tg2').hide();
                    $('#fgroup_id_tg3').hide();

                    $('#fgroup_id_tg1vp').show();
                    $('#fgroup_id_tg2vp').show();
                    $('#fgroup_id_tg3vp').show();
                }

                // help icons
                $('#fgroup_id_position_help_group').hide();
                $('#fgroup_id_duration_help_group').hide();
                $('#fgroup_id_vantage_help_group').hide();

                if ( value == 1 ) {
                    $('#fgroup_id_position_help_group').show();
                } else if ( value == 2 ) {
                    $('#fgroup_id_duration_help_group').show();
                } else if ( value == 3 ) {
                    $('#fgroup_id_vantage_help_group').show();
                }
            }

            function hideRemindersSection(){
                if( $('#id_start_enabled').is(':checked') ){
                    $('#id_remhdr').show();
                }else{
                    $('#id_remhdr').hide();
                }
            }

            function hidePositionGrading(){
                if( $('#id_start_enabled').is(':checked') ){
                    $("#id_detailgrading").val(0);
                    $("#id_detailgrading option[value=1]").hide();
                }else{
                    $("#id_detailgrading option[value=1]").show();
                }
            }
        }
    };

});