/* Freischaltung des Downloads.
   Prototyp: prueft die Eingaben, gibt den Download frei und meldet das Ereignis an den
   dataLayer. In WordPress uebernimmt das WPForms. */
(function () {
  var form = document.getElementById('leadmagnetForm');
  var karte = document.getElementById('leadmagnetCard');
  var erfolg = document.getElementById('leadmagnetSuccess');
  if (!form || !karte || !erfolg) return;

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    if (!form.reportValidity()) return;

    var plz = document.getElementById('lm-plz');
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'lw_leadmagnet_submit',
      magnet: 'verkaufs-checkliste',
      plz: plz ? plz.value || '' : '',
      page_path: location.pathname
    });

    karte.hidden = true;
    erfolg.hidden = false;
    erfolg.scrollIntoView({ behavior: 'smooth', block: 'center' });
    var link = document.getElementById('leadmagnetDownload');
    if (link) link.focus();
  });

  var link = document.getElementById('leadmagnetDownload');
  if (link) {
    link.addEventListener('click', function () {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: 'lw_leadmagnet_download',
        magnet: 'verkaufs-checkliste',
        page_path: location.pathname
      });
    });
  }
})();
