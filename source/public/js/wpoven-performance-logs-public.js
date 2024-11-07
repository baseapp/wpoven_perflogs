(function ($) {
  "use strict";

  /**
   * All of the code for your public-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  async function updateAdminBarData() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;
    
    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=update_admin_bar_data&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        const url = data.url;
        const displayText = data.display_text;
        var latestInfoElement = document.querySelector(
          "#wp-admin-bar-latest-info .ab-item"
        );

        if (latestInfoElement) {
          latestInfoElement.innerHTML = `<a href="${url}" style="color: white;" target="_blank">${displayText}</a>`;
        }
      }
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    updateAdminBarData();
  });
  
})(jQuery);
