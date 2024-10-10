/**
 * @file
 */
(function ($, Drupal, drupalSettings) {

    'use strict';

     Drupal.behaviors.heart_diocese = {
      attach: function (context, settings) {

        // For Diocese admin
        $(document).ready(function(){

            $('a.edit-this-diocese').once().click( function() {

                // Get the data from the button.
                let dioceseId = $(this).data('dioceseid');
                let dioceseName = $(this).data('diocesename');
                let dioceseAdmin = $(this).data('dioceseadmin');

                $('.manage-diocese-edit-admins .diocese-admin-name').text('');

                // Set Diocese ID into hidden field.
                $('.manage-diocese-edit-admins #hiddent_diocese_id').val(dioceseId);

                // Set the data in the edit section.
                $('.manage-diocese-edit-admins .name-of-diocese h3').text(dioceseName);

                if (dioceseId != 0) {
                    $.ajax({
                        type: "POST",
                        url:
                          drupalSettings.path.baseUrl +
                          "heart-diocese/admin-info",
                        data: {
                          diocese_id: dioceseId,
                          lang: drupalSettings.path.currentLanguage,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            let count = result.length;
                            if (result != 'NA') {
                                for(let i=0; i<result.length; i++) {
                                    let uid = result[i].uid;
                                    let adminName = result[i].name;
                                    if (count > 1) {
                                        $('.manage-diocese-edit-admins .diocese-admin-name').append('<p class="admin-name">'+adminName+' <a class="remove-admin" data-dioceseId="'+dioceseId+'" data-uid="'+uid+'">Remove</a></span></p>');
                                    } else {
                                        $('.manage-diocese-edit-admins .diocese-admin-name').append('<p class="admin-name">'+adminName+'</p>');
                                    }
                                }
                            } else {
                                $('.manage-diocese-edit-admins .diocese-admin-name').append('<p class="admin-name">NA</p>');
                            }
                            // Show details in edit header.
                            $('.manage-diocese-edit-admins').show();
                        },
                        error: function (xhr, status, error) {
                          // Handle error here
                          console.error(xhr.responseText);
                        },
                      });
                }
            });

            $('span.select-diff-diocese').once().click( function() {

                // Unset the data in the header edit section.
                $('.manage-diocese-edit-admins .name-of-diocese h3').text('');
                $('.manage-diocese-edit-admins .diocese-admin-name').text('');

                // Set Diocese ID into hidden field.
                $('.manage-diocese-edit-admins #hiddent_diocese_id').val('');

                // Hide details in edit header.
                $('.manage-diocese-edit-admins').hide();
            });

            $('a.upload-diocese-list').once().click( function() {
                $('.form-item-diocese-admin-list-upload').toggle();
            });

            $('.manage-diocese-edit-admins .diocese-admin-name').once().on('click', 'a.remove-admin', function() {
                var parish_remove = $(this).parent().parent().parent().find('.parish-admin-name').length;
                var diocese_remove = $(this).parent().parent().parent().find('.diocese-admin-name').length;
                console.log('hiii');
                if ($(this).text() == 'Remove') {
                    if (parish_remove == 1) {
                        $("#dialog-parish-remove-prompt").dialog();
                        let parishId = $(this).data('parishid');
                        let uid = $(this).data('uid');

                        $(".delete-parish-admin-prompt").attr('data-parishId', parishId);
                        $("#dialog-parish-remove-prompt").attr('data-uid', uid);
                    }
                    if (diocese_remove == 1) {
                        $("#dialog-diocese-remove-prompt").dialog();
                        let dioceseId = $(this).data('dioceseid');
                        let uid = $(this).data('uid');

                        $(".delete-diocese-admin-prompt").attr('data-dioceseId', dioceseId);
                        $("#dialog-diocese-remove-prompt").attr('data-uid', uid);
                    }

                }
            });

            $('#dialog-diocese-remove-prompt .delete-btn').once().click( function() {
                let dioceseId = $('#dialog-diocese-remove-prompt').attr('data-dioceseid');
                let uid = $("#dialog-diocese-remove-prompt").attr('data-uid');
                if (dioceseId != 0 && uid != null) {
                    $.ajax({
                        type: "POST",
                        url:
                            drupalSettings.path.baseUrl +
                            "heart-diocese/remove-admin-info",
                        data: {
                            diocese_id: dioceseId,
                            uid: uid,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            if (result == 'Deleted') {
                                $(".remove-admin[data-uid='" + uid + "']").parent().remove();
                                let newCount = $('.diocese-admin-name .admin-name').length;
                                if (newCount == 1) {
                                    $('.diocese-admin-name .admin-name .remove-admin').remove();
                                }
                                $('.ui-dialog').find('button.ui-dialog-titlebar-close').trigger('click');
                                window.location.reload();
                            } else if (result != 'Failure' && result != 'Deleted' && typeof result === 'object' && result !== null) {
                                // let count = result.length;
                                $('#dialog-diocese-remove-prompt p').html('<div class="dialog-body"><p>There is data associated with this admin. Please assign the content to another admin from the list below.</p></div>');
                                for(let i=0; i<result.length; i++) {
                                    let toUid = result[i].uid;
                                    let adminName = result[i].name;
                                    $('#dialog-diocese-remove-prompt div.dialog-body').append('<p><a class="assign-to-admin" data-dioceseId="'+dioceseId+'" data-toUid="'+toUid+'" data-fromUid="'+uid+'">'+adminName+'</a></p>');
                                }
                                $('#dialog-diocese-remove-prompt a.delete-btn').hide();
                            } else {
                                // $('#dialog-diocese-remove-prompt').dialog('close');
                                $('.ui-dialog').find('button.ui-dialog-titlebar-close').trigger('click');
                            }
                        }
                        });

                }
            });

            $('#dialog-diocese-remove-prompt').once().on('click', '.assign-to-admin', function() {
                let dioceseId = $(this).data('dioceseid');
                let toUid = $(this).data('touid');
                let fromUid = $(this).data('fromuid');

                if (dioceseId != 0 && toUid != 0 && fromUid != 0) {
                    $.ajax({
                        type: "POST",
                        url: drupalSettings.path.baseUrl + "heart-diocese/assign-diocese-admin-content",
                        data: {
                            diocese_id: dioceseId,
                            to_uid: toUid,
                            from_uid: fromUid,
                            lang: drupalSettings.path.currentLanguage,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            $('#dialog-diocese-remove-prompt div.dialog-body').remove();
                            $('#dialog-diocese-remove-prompt p').text('Content is assigned successfully. Please hit remove, if you want to delete the admin.');
                            $('#dialog-diocese-remove-prompt a.delete-btn').show();
                        },
                        error: function (xhr, status, error) {
                            // Handle error here
                            console.error(xhr.responseText);
                        },
                    });
                }
            });



            $('#dialog-diocese-remove-prompt .cancel-btn').once().click( function() {
                $('#dialog-diocese-remove-prompt').dialog('close');
                $('#dialog-diocese-remove-prompt div.dialog-body').remove();
                $('#dialog-diocese-remove-prompt p').text('Please confirm, if you want to delete.');
                $('#dialog-diocese-remove-prompt a.delete-btn').show();
            });

            $('a.edit-this-parish').once().click( function() {
                // Get the data from the button.
                let parishId = $(this).data('parishid');
                let parishName = $(this).data('parishname');
                let parishAdmin = $(this).data('parishadmin');

                $('.manage-parish-edit-admins .parish-admin-name').text('');

                // Set parish ID into hidden field.
                $('.manage-parish-edit-admins #hiddent_parish_id').val(parishId);

                // Set the data in the edit section.
                $('.manage-parish-edit-admins .name-of-parish h3').text(parishName);

                if (parishId != 0) {
                    $.ajax({
                        type: "POST",
                        url:
                          drupalSettings.path.baseUrl +
                          "heart-parish/admin-info",
                        data: {
                          parish_id: parishId,
                          lang: drupalSettings.path.currentLanguage,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            let count = result.length;
                            if (result != 'NA') {
                                for(let i=0; i<result.length; i++) {
                                    let uid = result[i].uid;
                                    let adminName = result[i].name;
                                    if (count > 1) {
                                        $('.manage-parish-edit-admins .parish-admin-name').append('<p class="admin-name">'+adminName+' <a class="remove-admin" data-parishId="'+parishId+'" data-uid="'+uid+'">Remove</a></span></p>');
                                    } else {
                                        $('.manage-parish-edit-admins .parish-admin-name').append('<p class="admin-name">'+adminName+'</p>');
                                    }
                                }
                            } else {
                                $('.manage-parish-edit-admins .parish-admin-name').append('<p class="admin-name">NA</p>');
                            }
                            // Show details in edit header.
                            $('.manage-parish-edit-admins').show();
                        },
                        error: function (xhr, status, error) {
                          // Handle error here
                          console.error(xhr.responseText);
                        },
                      });
                }
            });

            $('span.select-diff-parish').once().click( function() {

                // Unset the data in the header edit section.
                $('.manage-parish-edit-admins .name-of-parish h3').text('');
                $('.manage-parish-edit-admins .parish-admin-name').text('');

                // Set parish ID into hidden field.
                $('.manage-parish-edit-admins #hiddent_parish_id').val('');

                // Hide details in edit header.
                $('.manage-parish-edit-admins').hide();
            });

            $('a.upload-parish-list').once().click( function() {
                $('.form-item-parish-admin-list-upload').toggle();
            });

            $('#dialog-parish-remove-prompt a.delete-btn').once().click( function() {
                let parishId = $('#dialog-parish-remove-prompt').attr('data-parishid');
                let uid = $("#dialog-parish-remove-prompt").attr('data-uid');
                if (parishId != 0 && uid != null) {
                    $.ajax({
                        type: "POST",
                        url:
                            drupalSettings.path.baseUrl +
                            "heart-parish/remove-admin-info",
                        data: {
                            parish_id: parishId,
                            uid: uid,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            if (result == 'Deleted') {
                                $(".parish-admins-info .remove-admin[data-uid='" + uid + "']").parent().remove();
                                let newCount = $('.parish-admin-name .admin-name').length;
                                if (newCount == 1) {
                                    $('.parish-admin-name .admin-name .remove-admin').remove();
                                }
                                $('#dialog-parish-remove-prompt').dialog('close');
                                window.location.reload();
                            } else if (result != 'Failure' && result != 'Deleted' && typeof result === 'object' && result !== null) {
                                // let count = result.length;
                                $('#dialog-parish-remove-prompt p').html('<div class="dialog-body"><p>There is data associated with this admin. Please assign the content to another admin from the list below.</p></div>');
                                for(let i=0; i<result.length; i++) {
                                    let toUid = result[i].uid;
                                    let adminName = result[i].name;
                                    $('#dialog-parish-remove-prompt div.dialog-body').append('<p><a class="assign-to-admin" data-parishId="'+parishId+'" data-toUid="'+toUid+'" data-fromUid="'+uid+'">'+adminName+'</a></p>');
                                }
                                $('#dialog-parish-remove-prompt a.delete-btn').hide();
                            } else {
                                $('#dialog-parish-remove-prompt').dialog('close');
                            }
                        }
                        });

                }
            });

            $('#dialog-parish-remove-prompt').once().on('click', '.assign-to-admin', function() {
                let parishId = $(this).data('parishid');
                let toUid = $(this).data('touid');
                let fromUid = $(this).data('fromuid');

                if (parishId != 0 && toUid != 0 && fromUid != 0) {
                    $.ajax({
                        type: "POST",
                        url: drupalSettings.path.baseUrl + "heart-parish/assign-parish-admin-content",
                        data: {
                            parish_id: parishId,
                            to_uid: toUid,
                            from_uid: fromUid,
                            lang: drupalSettings.path.currentLanguage,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            $('#dialog-parish-remove-prompt div.dialog-body').remove();
                            $('#dialog-parish-remove-prompt p').text('Content is assigned successfully. Please hit remove, if you want to delete the admin.');
                            $('#dialog-parish-remove-prompt a.delete-btn').show();
                        },
                        error: function (xhr, status, error) {
                            // Handle error here
                            console.error(xhr.responseText);
                        },
                    });
                }
            });



            $('#dialog-parish-remove-prompt .cancel-btn').once().click( function() {
                $('#dialog-parish-remove-prompt').dialog('close');
                $('#dialog-parish-remove-prompt div.dialog-body').remove();
                $('#dialog-parish-remove-prompt p').text('Please confirm, if you want to delete.');
                $('#dialog-parish-remove-prompt a.delete-btn').show();
            });

        });

        // For Parish admin
        // $(document).ready(function(){


        // })
      }
    };
  })(jQuery, Drupal, drupalSettings);
