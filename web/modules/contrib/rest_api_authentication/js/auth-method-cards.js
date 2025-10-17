/**
 * @file
 * Authentication method card interaction functionality.
 */

(function ($, Drupal, once) {
    'use strict';
  
    Drupal.behaviors.authMethodCards = {
      attach: function (context, settings) {
        once('auth-method-cards', '.auth-method-cards', context).forEach(function (element) {
          setTimeout(function() {
            const radios = element.querySelectorAll("input[type=radio]");
            radios.forEach(function(radio) {
              const formItem = radio.closest(".form-item");
              if (formItem) {
                const card = document.createElement("div");
                card.className = "auth-method-card";
                if (radio.checked) {
                  card.classList.add("selected");
                }
                
                const label = formItem.querySelector("label");
                if (label) {
                  label.parentNode.insertBefore(card, label);
                  card.appendChild(label);
                }
                
                radio.addEventListener("change", function() {
                  document.querySelectorAll(".auth-method-card").forEach(function(c) {
                    c.classList.remove("selected");
                  });
                  if (this.checked) {
                    card.classList.add("selected");
                  }
                });
                
                card.addEventListener("click", function(e) {
                  if (e.target !== radio) {
                    radio.click();
                  }
                });
              }
            });
          }, 100);
        });
      }
    };
  
  })(jQuery, Drupal, once);