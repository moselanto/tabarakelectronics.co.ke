(function($){
  'use strict';
  var frame;
  function pick(e){
    e.preventDefault();
    if(frame){ frame.open(); return; }
    frame = wp.media({ title:'Select brand image', button:{ text:'Use image' }, multiple:false });
    frame.on('select', function(){
      var a = frame.state().get('selection').first().toJSON();
      var u = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
      $('#tabarak_brand_image_id').val(a.id);
      $('#tabarak_brand_image_preview').html('<img src="'+u+'" style="max-width:100px;height:auto;" />');
    });
    frame.open();
  }
  $(document).on('click', '.tabarak-brand-upload', pick);
  $(document).on('click', '.tabarak-brand-remove', function(e){
    e.preventDefault();
    $('#tabarak_brand_image_id').val('');
    $('#tabarak_brand_image_preview').empty();
  });
})(jQuery);
