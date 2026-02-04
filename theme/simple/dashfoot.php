<!-- Content End -->
</div>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
<!-- <script src='https://code.jquery.com/mobile/git/jquery.mobile-git.js'></script> -->
<script src='https://code.jquery.com/ui/1.10.3/jquery-ui.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js'></script>
<script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

<?php
echo $footer['scriptfoot'] ??='';
$notif = $footer['konfirm']??='';
?>
<script type="text/javascript">
	$(document).ready(function () {
		// Listen for the modal show event
	  $('#konfirmasi').on('show.bs.modal', function (event) {
	    // Get the button that triggered the modal
	    var button = $(event.relatedTarget);
	    
	    // Get the value of the data-bs-name attribute
	    var nama = button.data('bs-nama');
	    var id = button.data('bs-id');
	    
	    // Change the content of the modal body based on the value of the data-bs-name attribute
	    $(".modal-title").text('Hapus '+nama);
	    $(".modal-body").html('<?= $notif;?>');
	    $(".delbutton").attr("href", "?del="+id)
  	});


    $(".konten").hide(); // sembunyikan semua konten pada awalnya

    $('.info').click(function() {
        // Temukan konten terkait dengan data-target yang sesuai
        var target = $(this).data('target');
        var konten = $('.' + target);

        // Sembunyikan semua konten terlebih dahulu
        $(".konten").not(konten).slideUp();

        // Toggle (sembunyikan/tampilkan) konten yang diklik
        konten.slideToggle();
    });

    $('#nameInput').on('change', function() {
      if ($(this).val() === 'custom') {
        $('#customInputContainer').removeClass('d-none');
      } else {
        $('#customInputContainer').addClass('d-none');
      }
    });

    <?php     
    if (isset($footer['custom'])) {
      echo "$('#customInputContainer').removeClass('d-none');"."\n";
    }
    ?>

    $('#typeInput').on('change', function() {
      if ($(this).val() === 'select') {
        $('#optionsInputContainer').removeClass('d-none');
      } else {
        $('#optionsInputContainer').addClass('d-none');
      }
    });

    <?php     
    if (isset($editform['ff_type']) && $editform['ff_type'] == 'select') {
      echo "$('#optionsInputContainer').removeClass('d-none');"."\n";
    }
    ?>

    $('input[type=file]').on('change', function() {
        // Mendapatkan nama input file yang dipilih
        var inputName = $(this).attr('name');
        // Mendapatkan file yang dipilih
        var file = $(this).prop('files')[0];
        // Membuat elemen gambar untuk menampilkan preview
        var img = $('<img>', {
            class: 'img-fluid img-thumbnail',
            style: 'max-width: 200px',
            alt: inputName
        });
        // Membuat objek URL untuk file yang dipilih
        var url = URL.createObjectURL(file);
        // Menambahkan URL ke elemen gambar
        img.attr('src', url);
        // Menambahkan elemen gambar ke div preview yang sesuai
        $('#preview' + inputName).empty().append(img);
    });

    $("#fieldblock1, #fieldblock2, #fieldblock3, #fieldblock4").hide();
    <?php if (isset($footer['showfield']) && $footer['showfield'] != '') { echo $footer['showfield']; } ?>

    $("#service").change(function() {            
      field1 = field2 = field3 = field4 ='hide';
      switch ($('#service').val()) {
      <?php
      if (isset($footer['services']) && is_array($footer['services'])) { 
        foreach ($footer['services'] as $service) {
          echo '
          case \''.$service['file'].'\':';
          if (isset($service['data']) && count($service['data']) > 0) {
            $f = 1;
            foreach ($service['data'] as $datafield) {
              echo '
            field'.$f.' = \''.$datafield['label'].'\';';
              $f++;
            }
              echo '
            url = \''.str_replace('https', 'https:', $service['url']).'\';';
          }
          echo '
            break;';
        }
      }
      ?>
      }

      $("#fieldblock1, #fieldblock2, #fieldblock3, #fieldblock4").hide();

      if (field1 != 'hide'){ $("#fieldblock1").show(); $("#field1").html(field1);} 
      if (field2 != 'hide'){ $("#fieldblock2").show(); $("#field2").html(field2);} 
      if (field3 != 'hide'){ $("#fieldblock3").show(); $("#field3").html(field3);} 
      if (field4 != 'hide'){ $("#fieldblock4").show(); $("#field4").html(field4);} 
      $("#url").html('<a href="'+url+'" target="_blank">'+url+'</a>');

    });
  });
</script>

<script id="rendered-js" >
    $(function () {
      $("#sortable").sortable();
      $("#sortable").disableSelection();
    });
</script>

<script>
// Global: showCopySuccessModal fallback (soft gold style)
(function(){
  if (typeof window.showCopySuccessModal !== 'function') {
    window.showCopySuccessModal = function(){
      if (!document.getElementById('copySuccessModal')) {
        var modalHTML = '\n            <div id="copySuccessModal" class="copy-modal-overlay" style="display: none;">\n                <div class="copy-modal-content">\n                    <div class="copy-modal-icon">\n                        <i class="fas fa-check-circle"></i>\n                    </div>\n                    <div class="copy-modal-text">Sukses Tersalin</div>\n                </div>\n            </div>\n        ';
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        var style = document.createElement('style');
        style.textContent = '\n            .copy-modal-overlay {\n                position: fixed;\n                top: 0;\n                left: 0;\n                width: 100%;\n                height: 100%;\n                background: rgba(0, 0, 0, 0.5);\n                display: flex;\n                justify-content: center;\n                align-items: center;\n                z-index: 9999;\n                backdrop-filter: blur(3px);\n            }\n            .copy-modal-content {\n                background: linear-gradient(135deg, #ffd700, #ffed4e, #fff8dc);\n                border: 2px solid #ffd700;\n                border-radius: 15px;\n                padding: 30px 40px;\n                text-align: center;\n                box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3), 0 4px 12px rgba(0,0,0,0.15);\n                transform: scale(0.8);\n                animation: modalAppear 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;\n                max-width: 350px;\n                min-width: 280px;\n            }\n            .copy-modal-icon {\n                font-size: 3rem;\n                color: #2d5016;\n                margin-bottom: 15px;\n                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);\n            }\n            .copy-modal-text {\n                font-size: 1.2rem;\n                font-weight: 600;\n                color: #1a1a1a;\n                text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);\n                letter-spacing: 0.5px;\n            }\n            @keyframes modalAppear {\n                0% { transform: scale(0.8); opacity: 0; }\n                100% { transform: scale(1); opacity: 1; }\n            }\n            @keyframes modalDisappear {\n                0% { transform: scale(1); opacity: 1; }\n                100% { transform: scale(0.8); opacity: 0; }\n            }\n            .copy-modal-content.disappearing {\n                animation: modalDisappear 0.3s ease-in forwards;\n            }\n        ';
        document.head.appendChild(style);
      }
      var modal = document.getElementById('copySuccessModal');
      var content = modal.querySelector('.copy-modal-content');
      modal.style.display = 'flex';
      content.classList.remove('disappearing');
      setTimeout(function(){
        content.classList.add('disappearing');
        setTimeout(function(){ modal.style.display = 'none'; }, 300);
      }, 2000);
    };
  }
})();
</script>

<script>
// Global: copyToClipboard yang robust (fallback jika halaman tidak mendefinisikan sendiri)
(function(){
  if (typeof window.copyToClipboard !== 'function') {
    function getTextFromElement(el) {
      var text = '';
      if (!el) return text;
      var tag = (el.tagName || '').toLowerCase();
      if (tag === 'a' && el.href) {
        text = el.href;
      } else if (tag === 'input' || tag === 'textarea') {
        text = el.value;
      } else {
        text = (el.innerText || el.textContent || '').trim();
      }
      // Coba atribut data seperti data-full-url atau data-copy
      if (!text || text.indexOf('http') !== 0) {
        var dataAttr = el.getAttribute ? (el.getAttribute('data-full-url') || el.getAttribute('data-copy')) : '';
        if (dataAttr) text = dataAttr;
      }
      // Cari anchor terdekat jika masih belum URL penuh
      if ((!text || text.indexOf('http') !== 0) && el.closest) {
        var anchor = el.closest('a[href]');
        if (anchor && anchor.href) {
          text = anchor.href;
        } else {
          var parent = el.parentElement;
          if (parent) {
            var siblingLink = parent.querySelector('a[href]');
            if (siblingLink && siblingLink.href) text = siblingLink.href;
          }
        }
      }
      return text;
    }

    window.copyToClipboard = async function(input) {
      try {
        var textToCopy = '';
        if (typeof input === 'string') {
          // Jika string seperti selector (#/.), ambil elemen
          if (/^[#.\[]/.test(input)) {
            var elSel = document.querySelector(input);
            textToCopy = getTextFromElement(elSel);
          } else {
            textToCopy = input;
          }
        } else if (input && input.target) {
          // Event dari onclick
          textToCopy = getTextFromElement(input.target);
        } else if (input && (input.nodeType === 1 || input.tagName)) {
          // Elemen DOM langsung (mis. <i> icon)
          textToCopy = getTextFromElement(input);
        }

        if (!textToCopy) {
          alert('Tidak ada teks untuk disalin.');
          return;
        }

        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(textToCopy);
        } else {
          var ta = document.createElement('textarea');
          ta.value = textToCopy;
          ta.style.position = 'fixed';
          ta.style.left = '-999999px';
          ta.style.top = '-999999px';
          document.body.appendChild(ta);
          ta.focus();
          ta.select();
          document.execCommand('copy');
          ta.remove();
        }

        if (typeof window.showCopySuccessModal === 'function') {
          window.showCopySuccessModal();
        } else if (typeof window.showCopySuccessNotification === 'function') {
          window.showCopySuccessNotification();
        }
      } catch (err) {
        console.error('Gagal menyalin teks: ', err);
        alert('Gagal menyalin teks. Silakan salin manual.');
      }
    };
  }
})();
</script>
</body>
</html>