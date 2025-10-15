jQuery(function ($) {
  // Simple media uploader for Settings page fields
  function bindMediaField($wrap) {
    let frame;
    $wrap.on("click", ".idc-media-pick", function (e) {
      e.preventDefault();
      if (!frame) {
        frame = wp.media({
          title: "Select Image or Font File",
          button: { text: "Use this file" },
          multiple: false,
        });
        frame.on("select", function () {
          const att = frame.state().get("selection").first().toJSON();
          $wrap.find("input[type=hidden]").val(att.id);
          $wrap.find(".idc-preview").attr("src", att.url);
        });
      }
      frame.open();
    });

    $wrap.on("click", ".idc-media-clear", function (e) {
      e.preventDefault();
      $wrap.find("input[type=hidden]").val("0");
      $wrap.find(".idc-preview").attr("src", "");
    });
  }

  $(".idc-media-field").each(function () {
    bindMediaField($(this));
  });
});
