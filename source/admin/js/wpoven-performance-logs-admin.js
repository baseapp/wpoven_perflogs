(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
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

  function showModal(title, message, onOk) {
    // Create the modal elements
    const modalOverlay = document.createElement("div");
    modalOverlay.className = "modal-overlay";

    const modal = document.createElement("div");
    modal.className = "modal";

    const modalContent = document.createElement("div");
    modalContent.className = "modal-content";

    const modalHeader = document.createElement("div");
    modalHeader.className = "modal-header";
    modalHeader.innerHTML = `<h2 style="color: green;">${title}</h2>`;

    const modalBody = document.createElement("div");
    modalBody.className = "modal-body";
    modalBody.innerHTML = `<p>${message}</p>`;

    const modalFooter = document.createElement("div");
    modalFooter.className = "modal-footer";

    const okButton = document.createElement("button");
    okButton.className = "ok";
    okButton.innerText = "OK";
    okButton.style.backgroundColor = "#0073aa";
    okButton.style.border = "none"; // Remove border
    okButton.style.color = "white"; // Optional: Set text color for better contrast
    okButton.style.padding = "10px 20px"; // Optional: Add padding for better appearance
    okButton.style.cursor = "pointer"; // Optional: Change cursor on hover
    okButton.style.borderRadius = "5px";
    okButton.onclick = function () {
      document.body.removeChild(modalOverlay); // Remove the modal overlay
      document.body.removeChild(modal); // Remove the modal
      if (onOk) onOk(); // Call the onOk callback
    };

    modalFooter.appendChild(okButton);
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(modalBody);
    modalContent.appendChild(modalFooter);
    modal.appendChild(modalContent);

    document.body.appendChild(modalOverlay);
    document.body.appendChild(modal);

    // Optional: Style the modal overlay (semi-transparent background)
    modalOverlay.style.position = "fixed";
    modalOverlay.style.top = "0";
    modalOverlay.style.left = "0";
    modalOverlay.style.width = "100%";
    modalOverlay.style.height = "100%";
    modalOverlay.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
    modalOverlay.style.zIndex = "999"; // Ensure it is above other elements

    // Optional: Style the modal
    modal.style.position = "fixed";
    modal.style.zIndex = "1000"; // Ensure the modal is above the overlay
    modal.style.display = "flex";
    modal.style.alignItems = "center";
    modal.style.justifyContent = "center";
    modal.style.top = "0";
    modal.style.left = "0";
    modal.style.width = "100%";
    modal.style.height = "100%";

    // Style the modal content
    modalContent.style.backgroundColor = "white";
    modalContent.style.padding = "20px";
    modalContent.style.borderRadius = "5px";
    modalContent.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.1)";
    modalContent.style.width = "400px";
    modalContent.style.maxWidth = "90%";
  }

  async function wpovenPurgeAllLogs() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=wpoven_purge_all_logs&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        showModal(
          "Purge All Performance Logs!",
          "All log data purge successfully.",
          function () {
            // Refresh the page when OK is clicked
            location.reload();
          }
        );
      }
    }
  }

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
    // Remove the top-level menu item for the plugin.
    var element = document.querySelector(
      "li.toplevel_page_wpoven-performance-logs"
    );
    if (element) {
      element.remove();
    }

    // // remove extra menu title
    // const menuItems = document.querySelectorAll("li#toplevel_page_wpoven");
    // const menuArray = Array.from(menuItems);
    // for (let i = 1; i < menuArray.length; i++) {
    //   menuArray[i].remove();
    // }

    var purgeAllLogs = document.querySelector('[data-id="purge-all-logs"]');

    if (purgeAllLogs) {
      purgeAllLogs.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to Purge All Logs?")) {
          wpovenPurgeAllLogs();
        }
      });
    }

    var liElement = document.querySelector("li.performance-logs");
    if (liElement) {
      liElement.classList.remove("redux-group-tab-link-li");
      var firstAElement = liElement.querySelector("a");
      if (firstAElement) {
        firstAElement.remove();
      }
    }

    var purgeAllLogsLabel = document.querySelector(
      '[for="purge-all-logs-buttonset1"]'
    );

    if (purgeAllLogsLabel) {
      purgeAllLogsLabel.style.background = "#0178af";
      purgeAllLogsLabel.style.color = "white";
     
    }

    // Update initially
    updateAdminBarData();
  });

  $(document).ready(function () {
    $(".open-modal-btn").on("click", function () {
      var modalId = $(this).data("modal-id");
      var modal = $("#" + modalId);
      modal.css("display", "block");
      var closeButton = modal.find(".close");
      if (closeButton.length) {
        closeButton.on("click", function () {
          modal.css("display", "none");
        });
      }
    });
  });

  $("#toggleColumns").on("click", function () {
    $(".wp-list-table tr").toggleClass("is-expanded");
    $(this).text(function (i, text) {
      return text === "Show All Columns"
        ? "Hide Extra Columns"
        : "Show All Columns";
    });
  });
})(jQuery);
