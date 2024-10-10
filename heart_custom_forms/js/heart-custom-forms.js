/**
 * @file
 * User Custom Forms behaviors.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';
  Drupal.behaviors.heart_custom_forms = {
    attach: function (context, settings) {
      // Check the checkbox status on page load.
      checkCheckbox();

      // Listen for checkbox change events.
      $('#edit-terms-condition').change(function () {
        checkCheckbox();
      });

      // Function to check checkbox status and disable/enable the submit button.
      function checkCheckbox() {
        if ($('#edit-terms-condition').prop('checked')) {
          $('#edit-submit').prop('disabled', false);
        } else {
          $('#edit-submit').prop('disabled', true);
        }
      }
    }
  };
  $(document).ready(function () {
    $('#edit-sub-role-19').prop('disabled', true);

    $('a.edit-this-class').once().click(function () {
      // Get the data from the button.
      let classId = $(this).data('classid');
      // Get current langcode.
      var langCode = drupalSettings.path.currentLanguage;
      if (langCode == 'en') {
        var Url = '/manage-class-detail/' + classId;
      } else {
        var Url = '/' + langCode + '/manage-class-detail/' + classId;
      }
      $.ajax({
        url: Url,
        type: "POST",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (response) {
          // console.log(response.role);
          //Remove class view and show manage class form and learner view.
          $('.all-class-view').addClass('visually-hidden');
          if ($('.manage-class-form').hasClass('visually-hidden')) {
            $('.manage-class-form').removeClass('visually-hidden');
          }
          if ($('.learner-view').hasClass('visually-hidden')) {
            $('.learner-view').removeClass('visually-hidden');
          }
          if ($('.class-manage-heading').hasClass('visually-hidden')) {
            $('.class-manage-heading').removeClass('visually-hidden');
          }
          var $wrapper = $('.manage-class-form');
          var $wrapper2 = $('.learner-view');
          var $wrapper3 = $('.facilitator-view');
          var $wrapper4 = $('.add-more-license');
          $wrapper.html(response.form_html);
          $wrapper2.html(response.view_block_html);
          // Ensure drupalSettings are merged correctly
          if (typeof drupalSettings === 'undefined') {
            drupalSettings = {};
          }
          if (typeof response.settings !== 'undefined') {
            $.extend(drupalSettings, response.settings);
          }
          if (response.role == true) {
            if ($('.add-more-license').hasClass('visually-hidden')) {
              $('.add-more-license').removeClass('visually-hidden');
            }
            $wrapper4.html(response.licenserendered_form);
            if (typeof response.licensesettings !== 'undefined') {
              $.extend(true, drupalSettings, response.licensesettings);
            }
          }
          if (typeof response.settings_view !== 'undefined') {
            $.extend(drupalSettings, response.settings_view);
          }

          // Specifically handle drupalSettings.views if present
          if (response.settings_view && response.settings_view.views) {
            drupalSettings.views = response.settings_view.views;
          }
          // Reattach Drupal behaviors
          Drupal.attachBehaviors($wrapper[0], drupalSettings);
          Drupal.attachBehaviors($wrapper2[0], drupalSettings);
          if (response.role == true) {
            if ($('.facilitator-view').hasClass('visually-hidden')) {
              $('.facilitator-view').removeClass('visually-hidden');
            }
            $wrapper3.html(response.facilitator_view_block);
            if (typeof response.facilitator_settings_view !== 'undefined') {
              $.extend(drupalSettings, response.facilitator_settings_view);
            }

            // Specifically handle drupalSettings.views if present
            if (response.facilitator_settings_view && response.facilitator_settings_view.views) {
              drupalSettings.views = response.facilitator_settings_view.views;
            }
            Drupal.attachBehaviors($wrapper3[0], drupalSettings);
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX error:', status, error);
        }
      });
    });
    $('a.view-class-details').once().click(function () {
      // Get current langcode.
      var langCode = drupalSettings.path.currentLanguage;
      // Get the data from the button.
      let classId = $(this).data('classid');
      if (langCode == 'en') {
        var Url = '/manage-class-detail/' + classId +'?page=manage';
      } else {
        var Url = '/' + langCode + '/manage-class-detail/' + classId +'?page=manage';
      }
      console.log(Url);
      $.ajax({
        url: Url,
        type: "POST",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (response) {
          //Remove class view and show manage class form and learner view.
          //$('.all-class-view').addClass('visually-hidden');
          if ($('.manage-class-form').hasClass('visually-hidden')) {
            $('.manage-class-form').removeClass('visually-hidden');
          }
          if ($('.learner-view').hasClass('visually-hidden')) {
            $('.learner-view').removeClass('visually-hidden');
          }
          if ($('.class-manage-heading').hasClass('visually-hidden')) {
            $('.class-manage-heading').removeClass('visually-hidden');
          }
          var $wrapper = $('.manage-class-form');
          var $wrapper2 = $('.learner-view');
          var $wrapper3 = $('.facilitator-view');
          var $wrapper4 = $('.add-more-license');
          $wrapper.html(response.form_html);
          $wrapper2.html(response.view_block_html);
          // Ensure drupalSettings are merged correctly
          if (typeof drupalSettings === 'undefined') {
            drupalSettings = {};
          }
          if (typeof response.settings !== 'undefined') {
            $.extend(drupalSettings, response.settings);
          }
          if (response.role == true) {
            if ($('.add-more-license').hasClass('visually-hidden')) {
              $('.add-more-license').removeClass('visually-hidden');
            }
            $wrapper4.html(response.licenserendered_form);
            if (typeof response.licensesettings !== 'undefined') {
              $.extend(true, drupalSettings, response.licensesettings);
            }
          }
          if (typeof response.settings_view !== 'undefined') {
            $.extend(drupalSettings, response.settings_view);
          }

          // Specifically handle drupalSettings.views if present
          if (response.settings_view && response.settings_view.views) {
            drupalSettings.views = response.settings_view.views;
          }
          // Reattach Drupal behaviors
          Drupal.attachBehaviors($wrapper[0], drupalSettings);
          Drupal.attachBehaviors($wrapper2[0], drupalSettings);
          if (response.role == true) {
            if ($('.facilitator-view').hasClass('visually-hidden')) {
              $('.facilitator-view').removeClass('visually-hidden');
            }
            $wrapper3.html(response.facilitator_view_block);
            if (typeof response.facilitator_settings_view !== 'undefined') {
              $.extend(drupalSettings, response.facilitator_settings_view);
            }

            // Specifically handle drupalSettings.views if present
            if (response.facilitator_settings_view && response.facilitator_settings_view.views) {
              drupalSettings.views = response.facilitator_settings_view.views;
            }
            Drupal.attachBehaviors($wrapper3[0], drupalSettings);
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX error:', status, error);
        }
      });
    });

    $('.view-manage-users').once().on('click', 'a.edit-this-user', function (e) {
      // Get current langcode.
      var langCode = drupalSettings.path.currentLanguage;
      // Get the data from the button.
      let userId = $(this).data('userid');
      if (langCode == 'en') {
        var Url = '/user-profile-data/' + userId + '/edit';
      } else {
        var Url = '/' + langCode + '/user-profile-data/' + userId + '/edit';
      }
      console.log(Url);
      $.ajax({
        url: Url,
        type: "POST",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (response) {
          //Remove class view and show manage class form and learner view.
          $('.all-user-view').addClass('visually-hidden');
          if ($('.manage-profile-form').hasClass('visually-hidden')) {
            $('.manage-profile-form').removeClass('visually-hidden');
          }
          if ($('.profile-and-classes-link').hasClass('visually-hidden')) {
            $('.profile-and-classes-link').removeClass('visually-hidden');
            $('#user-profile-link').attr('data-userid', userId);
            $('#user-classes-link').attr('data-userid', userId);
          }
          var $wrapper = $('.manage-profile-form');
          $wrapper.html(response.form_html);
          // Ensure drupalSettings are merged correctly
          if (typeof drupalSettings === 'undefined') {
            drupalSettings = {};
          }
          if (typeof response.settings !== 'undefined') {
            $.extend(drupalSettings, response.settings);
          }
          // // Reattach Drupal behaviors
          Drupal.attachBehaviors($wrapper[0], drupalSettings);
        },
        error: function (xhr, status, error) {
          console.error('AJAX error:', status, error);
        }
      });
    });
    if ($('.heart-custom-forms-event-add-edit input[name="item_cost"]').val() == 'complimentary') {
      $('.heart-custom-forms-event-add-edit input[name="event_price"]').parent().hide();
      $('.heart-custom-forms-event-add-edit input[name="nav_item_number"]').parent().hide();
    }
    $('.heart-custom-forms-event-add-edit input[name="item_cost"]').click(function () {
      if ($(this).val() == 'complimentary') {
        $('.heart-custom-forms-event-add-edit input[name="event_price"]').parent().hide();
        $('.heart-custom-forms-event-add-edit input[name="event_price"]').val('');
        $('.heart-custom-forms-event-add-edit input[name="nav_item_number"]').parent().hide();
        $('.heart-custom-forms-event-add-edit input[name="nav_item_number"]').val('');
      } else {
        $('.heart-custom-forms-event-add-edit input[name="event_price"]').parent().show();
        $('.heart-custom-forms-event-add-edit input[name="nav_item_number"]').parent().show();
      }
    });

  });
  $(document).ajaxComplete(function () {
    //On click of choose another button remove form and show class view.
    $('.choose-another-class').once().click(function () {
      if ($('.all-class-view').hasClass('visually-hidden')) {
        $('.all-class-view').removeClass('visually-hidden');
      }
      $('.manage-class-form').addClass('visually-hidden');
      $('.learner-view').addClass('visually-hidden');
      $('.class-manage-heading').addClass('visually-hidden');
      $('.facilitator-view').addClass('visually-hidden');
      $('.add-more-license').addClass('visually-hidden');
      return false;
    });
    $('#all-class-tab').once().click(function () {
      if ($('.all-class-view').hasClass('visually-hidden')) {
        $('.all-class-view').removeClass('visually-hidden');
      }
      $('.manage-class-form').addClass('visually-hidden');
      $('.learner-view').addClass('visually-hidden');
      $('.class-manage-heading').addClass('visually-hidden');
      $('.facilitator-view').addClass('visually-hidden');
      $('.add-more-license').addClass('visually-hidden');
      return false;
    });
    $('a.cancel-invite').once().click(function () {
      if (confirm('Are you sure you want to cancel the invitation?')) {
        // Get the data from the button.
        var langCode = drupalSettings.path.currentLanguage;
        let classinviteId = $(this).data('classinviteid');
        let uId = $(this).data('userid');
        if (langCode == 'en') {
          var Url = '/class-facilatator/cancel-invite/' + uId + '/' + classinviteId;
        } else {
          var Url = '/' + langCode + '/class-facilatator/cancel-invite/' + uId + '/' + classinviteId;
        }
        var throbber = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
        $('body').append(throbber);
        $.ajax({
          url: Url,
          type: "POST",
          contentType: "application/json; charset=utf-8",
          dataType: "json",
          beforeSend: function () {
            // Show the throbber
            throbber.show();
          },
          success: function (response) {
            if (response.status == 'success') {
              throbber.remove();
              alert('Invitation canceled successfully');
              var $wrapper2 = $('.learner-view');
              var $wrapper3 = $('.facilitator-view');
              $wrapper2.html(response.view_block_html); // Replace old view with new
              $wrapper3.html(response.facilitator_view_block);

              // Ensure drupalSettings are merged correctly.
              if (typeof drupalSettings === 'undefined') {
                drupalSettings = {};
              }

              if (response.settings_view) {
                $.extend(drupalSettings, response.settings_view);
              }
              if (response.facilitator_settings_view) {
                $.extend(true, drupalSettings, response.facilitator_settings_view);
              }

              // Reattach Drupal behaviors to the updated view.
              Drupal.attachBehaviors($wrapper2[0], drupalSettings);
              Drupal.attachBehaviors($wrapper3[0], drupalSettings);
            } else {
              alert('Error: ' + response.message);
            }
          },
          error: function (xhr, status, error) {
            throbber.remove();
            console.error('AJAX error:', status, error);
            alert('An unexpected error occurred while cancel the invitation.');
          }
        });
      } else {
        // If the user clicks "Cancel", do nothing
        return false;
      }
    });
    $('a.resend-invite').once().click(function () {
      // alert('I m in');
      // return false;
      // Get the data from the button.
      var langCode = drupalSettings.path.currentLanguage;
      let classrefid = $(this).data('classrefid');
      let uId = $(this).data('userid');
      if (langCode == 'en') {
        var Url = '/class-facilatator/resend-invite/' + uId + '/' + classrefid;
      } else {
        var Url = '/' + langCode + '/class-facilatator/resend-invite/' + uId + '/' + classrefid;
      }
      var throbber = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
      $('body').append(throbber);
      $.ajax({
        url: Url,
        type: "POST",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        beforeSend: function () {
          // Show the throbber
          throbber.show();
        },
        success: function (response) {
          throbber.remove();
          if (response.status == 'success') {
            alert('Invitation sent successfully.');
            var $wrapper2 = $('.learner-view');
            var $wrapper3 = $('.facilitator-view');
            $wrapper2.html(response.view_block_html); // Replace old view with new
            $wrapper3.html(response.facilitator_view_block);

            // Ensure drupalSettings are merged correctly.
            if (typeof drupalSettings === 'undefined') {
              drupalSettings = {};
            }

            if (response.settings_view) {
              $.extend(drupalSettings, response.settings_view);
            }
            if (response.facilitator_settings_view) {
              $.extend(true, drupalSettings, response.facilitator_settings_view);
            }

            // Reattach Drupal behaviors to the updated view.
            Drupal.attachBehaviors($wrapper2[0], drupalSettings);
            Drupal.attachBehaviors($wrapper3[0], drupalSettings);
          } else {
            alert('Error: ' + response.message);
          }
        },
        error: function (xhr, status, error) {
          throbber.remove();
          console.error('AJAX error:', status, error);
          alert('An unexpected error occurred while resending the invitation.');
        }
      });
    });
    $('a#user-classes-link').once().click(function () {
      // Get current langcode.
      var langCode = drupalSettings.path.currentLanguage;
      // Get the data from the button.
      let userId = $(this).data('userid');
      //console.log(userId);
      if (langCode == 'en') {
        var Url = '/user-classes-data/' + userId;
      } else {
        var Url = '/' + langCode + '/user-classes-data/' + userId;
      }
      $.ajax({
        url: Url,
        type: "POST",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (response) {
          //Remove class view and show user-classes-form.
          $('.all-user-view').addClass('visually-hidden');
          $('.manage-profile-form').addClass('visually-hidden');
          //Remove hidden class from user class listing form.
          if ($('.user-classes-form').hasClass('visually-hidden')) {
            $('.user-classes-form').removeClass('visually-hidden');
          }
          if ($('.profile-and-classes-link').hasClass('visually-hidden')) {
            $('.profile-and-classes-link').removeClass('visually-hidden');
            $('#user-profile-link').attr('data-userid', userId);
            $('#user-classes-link').attr('data-userid', userId);
          } else {
            $('#user-profile-link').attr('data-userid', userId);
            $('#user-classes-link').attr('data-userid', userId);
          }
          var $wrapper = $('.user-classes-form');
          $wrapper.html(response.form_html);
          // Ensure drupalSettings are merged correctly
          if (typeof drupalSettings === 'undefined') {
            drupalSettings = {};
          }
          if (typeof response.settings !== 'undefined') {
            $.extend(drupalSettings, response.settings);
          }
          // // Reattach Drupal behaviors
          Drupal.attachBehaviors($wrapper[0], drupalSettings);
        },
        error: function (xhr, status, error) {
          console.error('AJAX error:', status, error);
        }
      });
    });
    $('a#user-profile-link').once().click(function () {
      // Get current langcode.
      var langCode = drupalSettings.path.currentLanguage;
      // Get the data from the button.
      let userId = $(this).data('userid');
      if (langCode == 'en') {
        var Url = '/user-profile-data/' + userId + '/edit';
      } else {
        var Url = '/' + langCode + '/user-profile-data/' + userId + '/edit';
      }
      $.ajax({
        url: Url,
        type: "POST",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (response) {
          //Remove class view and show manage-profile-form and learner view.
          $('.all-user-view').addClass('visually-hidden');
          $('.user-classes-form').addClass('visually-hidden');
          if ($('.manage-profile-form').hasClass('visually-hidden')) {
            $('.manage-profile-form').removeClass('visually-hidden');
          }
          //Add data attribute to profile link and class link.
          if ($('.profile-and-classes-link').hasClass('visually-hidden')) {
            $('.profile-and-classes-link').removeClass('visually-hidden');
            $('#user-profile-link').attr('data-userid', userId);
            $('#user-classes-link').attr('data-userid', userId);
          } else {
            $('#user-profile-link').attr('data-userid', userId);
            $('#user-classes-link').attr('data-userid', userId);
          }
          var $wrapper = $('.manage-profile-form');
          $wrapper.html(response.form_html);
          // Ensure drupalSettings are merged correctly
          if (typeof drupalSettings === 'undefined') {
            drupalSettings = {};
          }
          if (typeof response.settings !== 'undefined') {
            $.extend(drupalSettings, response.settings);
          }
          // Reattach Drupal behaviors
          Drupal.attachBehaviors($wrapper[0], drupalSettings);
        },
        error: function (xhr, status, error) {
          console.error('AJAX error:', status, error);
        }
      });
    });
  });
  $(document).ajaxComplete(function(){
    $(".views-exposed-form-class-learners-view-block-1 table tr").each(function() {
      var $percentage = $(this).find('.progress-bar.class-progress-bar').attr('data-classProgress');
      if ($percentage) {
        $(this).find('.progress-bar.class-progress-bar').css('width', $percentage + '%');
      }
    });
  });
  $('.heart-custom-forms-user-registration').submit(function () {
    // Re-enable the disabled field just before form submission
    $('#edit-sub-role-19').prop('disabled', false);
  });
}(jQuery, Drupal, drupalSettings));

(function ($, Drupal) {
  Drupal.behaviors.customCertificateBehavior = {
    attach: function (context, settings) {

      toggleCompletionPercent($('input[name="certificate"]:checked').val());


      $('input[name="certificate"]', context).once('certificateBehavior').on('change', function () {
        toggleCompletionPercent($(this).val());
      });


      function toggleCompletionPercent(value) {

        if (value == '1') {
          $('.completion-percent-field').parent().show();
          $('.completion-percent-field').prop('required', true);

        } else {

          $('.completion-percent-field').parent().hide();
          $('.completion-percent-field').val('');
          $('.completion-percent-field').prop('required', false);
          $('.completion-percent-field').val('');
        }
      }
    }
  };
})(jQuery, Drupal);