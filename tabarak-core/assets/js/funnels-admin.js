/* Tabarak Core - funnel admin: media picker for the hero image */
jQuery(function ($) {
  var frame;
  $(document).on('click', '.tf-image-pick', function (e) {
    e.preventDefault();
    if (frame) { frame.open(); return; }
    frame = wp.media({ title: 'Choose hero image', library: { type: 'image' }, multiple: false });
    frame.on('select', function () {
      var a = frame.state().get('selection').first().toJSON();
      $('.tf-image-url').val(a.url);
      var img = $('.tf-admin-img');
      if (\!img.length) { img = $('<img class="tf-admin-img" alt="">').insertAfter($('.tf-image-pick').parent()); }
      img.attr('src', a.url);
    });
    frame.open();
  });
  $(document).on('click', '.tf-image-clear', function (e) {
    e.preventDefault(); $('.tf-image-url').val(''); $('.tf-admin-img').remove();
  });
});
