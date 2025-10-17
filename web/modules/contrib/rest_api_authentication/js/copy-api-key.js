/**
 * @file
 * Copy API key functionality.
 */

window.copyApiKey = function() {
  const field = document.getElementById('rest_api_authentication_token_key');
  if (field && field.value) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(field.value).then(function() {
        showCopySuccess();
      }).catch(function() {
        fallbackCopy(field);
      });
    } else {
      fallbackCopy(field);
    }
  }
};

function fallbackCopy(field) {
  field.select();
  field.setSelectionRange(0, 99999);
  try {
    document.execCommand('copy');
    showCopySuccess();
  } catch (err) {
    console.log('Copy failed');
  }
}

function showCopySuccess() {
  const btn = document.querySelector('.copy-btn');
  if (btn) {
    const original = btn.value;
    btn.value = 'Copied!';
    setTimeout(function() {
      btn.value = original;
    }, 2000);
  }
} 