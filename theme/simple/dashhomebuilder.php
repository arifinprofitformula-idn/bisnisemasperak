<?php 
$head['pagetitle']='Home Builder';
$head['scripthead'] = '
<style>
  #canvas {
    display: flex;
    flex-wrap: wrap; /* Agar modul pindah ke baris berikutnya */
    gap: 10px; /* Jarak antar modul */
    padding: 10px;
    background-color: #f9f9f9;
    min-height: 200px;
    border: 2px dashed #ccc;
  }

  #canvas {
  display: flex;
  flex-wrap: wrap; /* Membungkus item ke baris baru */
  gap: 10px; /* Jarak antar item */
  justify-content: center; /* Pusatkan item */
}

.canvas-item {
  flex: 1 1 calc(33.333% - 20px); /* Ukuran fleksibel: 1/3 lebar */
  box-sizing: border-box; /* Sertakan padding dan border dalam ukuran */
  min-width: 150px; /* Batas minimum untuk menjaga responsif */
}

.canvas-item {
  position: relative;
  display: flex;
  flex-direction: column; /* Susunan vertikal */
  align-items: center; /* Pusatkan horizontal */
  justify-content: center; /* Pusatkan vertikal */
  padding: 10px;
  border: 1px solid #ccc;
  background-color: #121527;
  color: #ffffff;
  font-weight: bold;
  text-align: center;
  border-radius: 4px;
}

.canvas-item .buttons {
  display: flex;
  gap: 10px; /* Jarak antar tombol */
  margin-top: 10px;
}

.canvas-item .delete-button,
.canvas-item .setting-button {
  border: none;
  padding: 5px 8px;
  cursor: pointer;
  color: #ffffff;
  border-radius: 4px;
}

.canvas-item .delete-button {
  background-color: #f44336; /* Warna merah */
}

.canvas-item .setting-button {
  background-color: #4caf50; /* Warna hijau */
}


  #modules {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); /* Responsif grid */
    gap: 10px; /* Jarak antar modul */
    padding: 5px;
    max-width: 100%; /* Agar tidak melampaui lebar layar */
    margin-bottom:20px;
  }

  .module {
    background-color: #6f73aa;
    color: white;
    text-align: center;
    padding: 5px;
    border-radius: 5px;
    cursor: grab;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);    
    height: 35px;
  }

  @media (min-width: 768px) {
    #modules {
      display: flex;
      flex-wrap: wrap; /* Agar modul tetap tersusun horizontal */
      justify-content: flex-start;           
    }

    .module {
      flex: 0 0 150px; /* Ukuran tetap di layar besar */      
    }
  }
</style>
';
showheader($head); ?>

<!-- Modules Section -->
<div id="modules">
  <?php 
  $modulhome = array(
    'text' => 'Text Box',
    'informasi' => 'Informasi',
    'affiliasi' => 'Info Affiliasi',
    'penghasilan' => 'Penghasilan Anda',
    'pesanan' => 'Pesanan Anda',
    'klienbaru' => 'Klien Baru',
    'akses' => 'Akses Produk',
    'landingpage' => 'Landing Page',
    'grafikvisitor' => 'Grafik Visitor',
    'leaderboard' => 'Leaderboard'
  );

  $modulhome = apply_filter('modulhome',$modulhome);

  foreach ($modulhome as $key => $value) {
    echo '<div class="module" id="'.$key.'">'.$value.'</div>'."\n";
  }
  
  ?>
</div>

<!-- Canvas Section -->
<div id="canvas"></div>
<button id="saveButton" class="btn btn-success mt-3">Simpan Urutan</button>

<div class="modal fade" id="moduleSettingsModal" tabindex="-1" aria-labelledby="moduleSettingsLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="moduleSettingsLabel">Pengaturan Modul</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Konten akan dimuat di sini -->
        <div id="moduleSettingsContent">Memuat...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="saveSettingsButton">Simpan</button>
      </div>
    </div>
  </div>
</div>


<?php
$footer['scriptfoot'] = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
const modules = document.getElementById("modules");
new Sortable(modules, {
  group: {
    name: "shared",
    pull: "clone",
    put: false,
  },
  animation: 150,
  sort: false,
});

const canvas = document.getElementById("canvas");
new Sortable(canvas, {
  group: {
    name: "shared",
    pull: false,
    put: true,
  },
  animation: 150,
  onAdd: function (evt) {
    const item = evt.item;

    // Simpan ID asli dari daftar modul ke atribut data
    const originalId = item.id;
    item.setAttribute("data-module-id", originalId);

    // Ubah elemen menjadi item canvas
    item.classList.remove("module");
    item.classList.add("canvas-item");

    // Tambahkan ID unik untuk elemen di canvas
    const uniqueId = `module-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
    item.id = uniqueId;

    // Buat container untuk tombol
    const buttonContainer = document.createElement("div");
    buttonContainer.className = "buttons";

    // Tombol Delete
    const deleteButton = document.createElement("button");
    deleteButton.className = "delete-button";
    deleteButton.innerHTML = \'<i class=\"fa-solid fa-trash-can\"></i>\';
    deleteButton.onclick = function () {
      item.remove();
    };

    // Tombol Setting
    const settingButton = document.createElement("button");
    settingButton.className = "setting-button";
    settingButton.innerHTML = \'<i class="fa-solid fa-screwdriver-wrench"></i>\';
    settingButton.onclick = function () {
      const moduleId = item.getAttribute("data-module-id"); // Ambil data-module-id dari elemen
      const canvasId = item.id; // Ambil canvasId dari elemen

      // Muat konten modal
      $("#moduleSettingsContent").html("Memuat...");
      $("#moduleSettingsModal").modal("show");

      // Panggil formmodul dengan data
      $.ajax({
        url: "'.$weburl.'formmodul.php",
        method: "GET",
        data: { moduleId: moduleId, canvasId: canvasId },
        success: function (response) {
          $("#moduleSettingsContent").html(response); // Tampilkan konten di modal
        },
        error: function () {
          $("#moduleSettingsContent").html("Gagal memuat data.");
        },
      });

      // Tambahkan event untuk tombol Simpan
      $("#saveSettingsButton").off("click").on("click", function () {
        // Ambil data dari form
        const formData = $("#moduleSettingsForm").serializeArray();
        const settings = {};

        // Ubah formData menjadi objek JSON
        formData.forEach((field) => {
          settings[field.name] = field.value;
        });

        // Ambil moduleId dan canvasId dari atribut data pada modal
        const moduleId = $("#moduleSettingsModal").data("moduleId");
        const canvasId = $("#moduleSettingsModal").data("canvasId");

        // Susun data yang akan dikirim
        const payload = {
          module: moduleId,
          moduleId: canvasId,
          settings: settings,
        };

        // Kirim data ke savesettings.php dalam bentuk JSON
        $.ajax({
          url: "'.$weburl.'savesettings.php",
          method: "POST",
          contentType: "application/json", // Pastikan header JSON
          data: JSON.stringify(payload),  // Stringify payload menjadi JSON
          success: function (response) {
            console.log(response); // Log respons dari server
            try {
              const result = JSON.parse(response);
              if (result.status === "success") {
                alert(result.message);
                $("#moduleSettingsModal").modal("hide");
              } else {
                alert(result.message || "Gagal menyimpan pengaturan.");
              }
            } catch (error) {
              console.error("Invalid JSON:", error);
              alert("Terjadi kesalahan pada respons server.");
            }
          },
          error: function () {
            alert("Gagal menyimpan pengaturan.");
          },
        });
      });
    };

    // Tambahkan tombol ke container
    buttonContainer.appendChild(deleteButton);
    buttonContainer.appendChild(settingButton);

    // Tambahkan container tombol ke item
    item.appendChild(buttonContainer);
  },
});

function getCanvasOrder() {
  const canvasItems = document.querySelectorAll("#canvas .canvas-item");
  const order = [];

  canvasItems.forEach((item) => {
      order.push(item.id); // Ambil ID unik modul
  });

  return order; // Kembalikan urutan dalam array
}

document.getElementById("saveButton").addEventListener("click", function () {
  const canvasItems = document.querySelectorAll("#canvas .canvas-item");
  const order = [];

  canvasItems.forEach((item, index) => {
    order.push({
      position: index + 1, // Posisi di canvas
      moduleId: item.getAttribute("data-module-id"), // ID asli modul
      canvasId: item.id, // ID unik di canvas
    });
  });

  // Kirim data ke server
  $.ajax({
    url: "'.$weburl.'canvas.php",
    method: "POST",
    data: { order: JSON.stringify(order) },
    success: function (response) {
      alert("Canvas berhasil disimpan!");
    },
    error: function () {
      alert("Gagal menyimpan canvas.");
    },
  });
});

$(document).ready(function () {
  $.ajax({
    url: "'.$weburl.'canvas.php",
    method: "GET",
    success: function (response) {
      const order = JSON.parse(response); // Ambil data urutan dari server

      const canvas = document.getElementById("canvas");
      order.forEach((moduleData) => {
        const { moduleId, canvasId } = moduleData;

        // Buat elemen baru untuk canvas berdasarkan data
        const newModule = document.createElement("div");
        newModule.classList.add("canvas-item");
        newModule.id = canvasId; // ID unik untuk elemen di canvas
        newModule.setAttribute("data-module-id", moduleId); // ID asli dari modul
        newModule.style.backgroundColor = "#121527";

        // Ambil teks modul dari daftar modul asli (atau teks default jika modul hilang)
        const moduleText = document.getElementById(moduleId)?.textContent || "Modul Hilang";
        newModule.textContent = moduleText;

        // Buat container tombol
        const buttonContainer = document.createElement("div");
        buttonContainer.className = "buttons";

        // Tombol Delete
        const deleteButton = document.createElement("button");
        deleteButton.className = "delete-button";
        deleteButton.innerHTML = \'<i class="fa-solid fa-trash-can"></i>\';
        deleteButton.onclick = function () {
          newModule.remove();
        };

        // Tombol Setting
        const settingButton = document.createElement("button");
        settingButton.className = "setting-button";
        settingButton.innerHTML = \'<i class="fa-solid fa-screwdriver-wrench"></i>\';
        settingButton.onclick = function () {
          const moduleId = newModule.getAttribute("data-module-id"); // Ambil data-module-id dari elemen
          const canvasId = newModule.id; // Ambil canvasId dari elemen

          // Muat konten modal
          $("#moduleSettingsContent").html("Memuat...");
          $("#moduleSettingsModal").modal("show");

          // Panggil formmodul dengan data
          $.ajax({
            url: "'.$weburl.'formmodul.php",
            method: "GET",
            data: { moduleId: moduleId, canvasId: canvasId },
            success: function (response) {
              $("#moduleSettingsContent").html(response); // Tampilkan konten di modal
            },
            error: function () {
              $("#moduleSettingsContent").html("Gagal memuat data.");
            },
          });

          // Tambahkan event untuk tombol Simpan
          $("#saveSettingsButton").off("click").on("click", function () {
            // Ambil data dari form
            const formData = $("#moduleSettingsForm").serializeArray();
            const settings = {};

            // Ubah formData menjadi objek JSON
            formData.forEach((field) => {
              settings[field.name] = field.value;
            });

            // Ambil moduleId dan canvasId dari atribut data pada modal
            const moduleId = $("#moduleSettingsModal").data("moduleId");
            const canvasId = $("#moduleSettingsModal").data("canvasId");

            // Susun data yang akan dikirim
            const payload = {
              module: moduleId,
              moduleId: canvasId,
              settings: settings,
            };

            // Kirim data ke savesettings.php dalam bentuk JSON
            $.ajax({
              url: "'.$weburl.'savesettings.php",
              method: "POST",
              contentType: "application/json", // Pastikan header JSON
              data: JSON.stringify(payload),  // Stringify payload menjadi JSON
              success: function (response) {
                console.log(response); // Log respons dari server
                try {
                  const result = JSON.parse(response);
                  if (result.status === "success") {
                    alert(result.message);
                    $("#moduleSettingsModal").modal("hide");
                  } else {
                    alert(result.message || "Gagal menyimpan pengaturan.");
                  }
                } catch (error) {
                  console.error("Invalid JSON:", error);
                  alert("Terjadi kesalahan pada respons server.");
                }
              },
              error: function () {
                alert("Gagal menyimpan pengaturan.");
              },
            });
          });


        };

        // Tambahkan tombol ke container tombol
        buttonContainer.appendChild(deleteButton);
        buttonContainer.appendChild(settingButton);

        // Tambahkan container tombol ke elemen modul
        newModule.appendChild(buttonContainer);

        // Tambahkan elemen ke canvas
        canvas.appendChild(newModule);
      });
    },
    error: function () {
      alert("Gagal memuat urutan.");
    },
  });

});
</script>';

showfooter($footer);
