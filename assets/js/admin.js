(function($) {
    'use strict';

    $(document).ready(function() {
        // Manejo de pestañas
        $('.Nabi-webp-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-pane').removeClass('active');
            $(target + '-tab').addClass('active');
        });

        // Formulario de configuración
        $('#Nabi-webp-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $result = $('#Nabi-webp-settings-result');
            
            $.ajax({
                url: NabiWebP.ajax_url,
                type: 'POST',
                data: {
                    action: 'NABI_webp_save_settings',
                    nonce: NabiWebP.nonce,
                    filter_year: $('#filter_year').val(),
                    filter_month: $('#filter_month').val()
                },
                success: function(response) {
                    if (response.success) {
                        $result
                            .removeClass('error')
                            .addClass('success')
                            .html('<span class="dashicons dashicons-yes-alt"></span>' + response.data.message)
                            .show();
                        // Recargar lista si cambia la configuración
                        loadImagesList();
                    } else {
                        $result
                            .removeClass('success')
                            .addClass('error')
                            .html('<span class="dashicons dashicons-warning"></span>' + (response.data.message || 'Error al guardar'))
                            .show();
                    }
                },
                error: function() {
                    $result
                        .removeClass('success')
                        .addClass('error')
                        .html('<span class="dashicons dashicons-warning"></span>Error de conexión')
                        .show();
                }
            });
        });

        const $convertBtn = $('#Nabi-webp-convert-btn');
        const $convertSelectedBtn = $('#Nabi-webp-convert-selected-btn');
        const $scanBtn = $('#Nabi-webp-scan-btn');
        const $progress = $('#Nabi-webp-progress');
        const $progressFill = $progress.find('.Nabi-webp-progress-fill');
        const $progressText = $progress.find('.Nabi-webp-progress-text');
        const $progressDetails = $('#Nabi-webp-progress-details');
        const $result = $('#Nabi-webp-result');
        const $totalCount = $('#Nabi-webp-total-count');
        const $converted = $('#Nabi-webp-converted');
        const $total = $('#Nabi-webp-total');
        const $imagesList = $('#Nabi-webp-images-list');
        const $selectAll = $('#Nabi-webp-select-all');
        const $selectedCount = $('#Nabi-webp-selected-count');

        let isConverting = false;
        let totalConverted = 0;
        let totalImages = 0;
        let selectedIds = [];

        // Cargar lista inicial
        loadImagesList();

        // Botón para actualizar lista
        $scanBtn.on('click', function(e) {
            e.preventDefault();
            loadImagesList();
        });

        // Botón para convertir todas las imágenes
        $convertBtn.on('click', function(e) {
            e.preventDefault();
            
            if (isConverting) return;

            if (!confirm(NabiWebP.strings.confirm_convert)) return;

            startConversion(false);
        });

        // Botón para convertir seleccionadas
        $convertSelectedBtn.on('click', function(e) {
            e.preventDefault();
            
            if (isConverting) return;

            const count = selectedIds.length;
            if (count === 0) return;

            if (!confirm('¿Estás seguro de que deseas convertir las ' + count + ' imágenes seleccionadas?')) return;

            startConversion(true);
        });

        // Manejo de "Seleccionar Todas"
        $selectAll.on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.Nabi-webp-image-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Manejo de checkboxes individuales (delegado)
        $imagesList.on('change', '.Nabi-webp-image-checkbox', function() {
            const id = parseInt($(this).val());
            const isChecked = $(this).is(':checked');

            if (isChecked) {
                if (!selectedIds.includes(id)) selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
                $selectAll.prop('checked', false);
            }

            updateSelectionUI();
        });

        /**
         * Carga la lista de imágenes
         */
        function loadImagesList() {
            $.ajax({
                url: NabiWebP.ajax_url,
                type: 'POST',
                data: {
                    action: 'NABI_webp_get_images_list',
                    nonce: NabiWebP.nonce,
                    limit: -1
                },
                beforeSend: function() {
                    $imagesList.html('<div class="Nabi-webp-loading-images"><span class="spinner is-active"></span> ' + NabiWebP.strings.loading || 'Cargando imágenes...' + '</div>');
                    $scanBtn.prop('disabled', true);
                    selectedIds = [];
                    updateSelectionUI();
                },
                success: function(response) {
                    if (response.success) {
                        const images = response.data.images;
                        $totalCount.text(images.length);
                        
                        if (images.length === 0) {
                            $imagesList.html('<p>' + NabiWebP.strings.no_images + '</p>');
                            return;
                        }

                        let html = '<div class="Nabi-webp-grid">';
                        images.forEach(function(img) {
                            html += `
                                <div class="Nabi-webp-image-item" data-id="${img.id}">
                                    <label>
                                        <input type="checkbox" class="Nabi-webp-image-checkbox" value="${img.id}">
                                        <div class="Nabi-webp-image-preview">
                                            ${img.thumbnail ? `<img src="${img.thumbnail}" alt="${img.title}">` : '<span class="dashicons dashicons-format-image"></span>'}
                                        </div>
                                        <div class="Nabi-webp-image-info">
                                            <span class="Nabi-webp-image-title">${img.title || 'ID: ' + img.id}</span>
                                            <span class="Nabi-webp-image-meta">${img.mime.split('/')[1].toUpperCase()} | ${img.date}</span>
                                        </div>
                                    </label>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $imagesList.html(html);
                    } else {
                        $imagesList.html('<p class="error">' + (response.data.message || 'Error al cargar lista') + '</p>');
                    }
                },
                error: function() {
                    $imagesList.html('<p class="error">Error de conexión al cargar lista</p>');
                },
                complete: function() {
                    $scanBtn.prop('disabled', false);
                }
            });
        }

        /**
         * Actualiza la UI de selección
         */
        function updateSelectionUI() {
            const count = selectedIds.length;
            $selectedCount.text(count + ' seleccionadas');
            
            if (count > 0) {
                $convertSelectedBtn.show().prop('disabled', false);
                $convertBtn.hide();
            } else {
                $convertSelectedBtn.hide().prop('disabled', true);
                $convertBtn.show();
            }
        }

        /**
         * Inicia la conversión
         */
        function startConversion(useSelected) {
            isConverting = true;
            totalConverted = 0;
            totalImages = useSelected ? selectedIds.length : parseInt($totalCount.text()) || 0;

            if (totalImages === 0) {
                isConverting = false;
                showResult('error', NabiWebP.strings.no_images);
                return;
            }

            $convertBtn.prop('disabled', true).text('Convirtiendo...');
            $convertSelectedBtn.prop('disabled', true).text('Convirtiendo...');
            $progress.show();
            $result.hide();
            $progressDetails.html('');
            $progressFill.css('width', '5%');
            $progressText.text('Iniciando conversión...');
            $total.text(totalImages);
            $converted.text('0');

            convertBatch(0, useSelected ? selectedIds : []);
        }

        /**
         * Convierte un lote de imágenes
         */
        function convertBatch(offset, ids) {
            const data = {
                action: 'NABI_webp_convert_all',
                nonce: NabiWebP.nonce,
                batch_size: 20,
                offset: offset,
                skip_references: true
            };

            if (ids && ids.length > 0) {
                data.image_ids = ids;
            }

            $.ajax({
                url: NabiWebP.ajax_url,
                type: 'POST',
                timeout: 300000,
                data: data,
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        totalConverted += data.converted;

                        // Actualizar progreso
                        const percentage = totalImages > 0 ? Math.min(100, (totalConverted / totalImages) * 100) : 0;
                        $progressFill.css('width', percentage + '%');
                        $converted.text(totalConverted);
                        $progressText.text(
                            data.message || NabiWebP.strings.converting
                        );

                        // Actualizar detalles
                        let detailsHtml = $progressDetails.html();
                        if (data.results && data.results.length > 0) {
                            data.results.forEach(function(result) {
                                detailsHtml += `<div class="progress-item ${result.success ? 'success' : 'error'}">
                                    ID ${result.id}: ${result.message}
                                </div>`;
                            });
                        }
                        $progressDetails.html(detailsHtml);
                        $progressDetails.scrollTop($progressDetails[0].scrollHeight);

                        if (data.completed) {
                            finishConversion(true, NabiWebP.strings.success + '. Total: ' + totalConverted);
                        } else if (data.has_more) {
                            setTimeout(function() {
                                convertBatch(data.offset, ids);
                            }, 300);
                        } else {
                            finishConversion(true, NabiWebP.strings.success);
                        }
                    } else {
                        finishConversion(false, response.data.message || NabiWebP.strings.error);
                    }
                },
                error: function() {
                    finishConversion(false, 'Error de conexión');
                }
            });
        }

        /**
         * Finaliza la conversión
         */
        function finishConversion(success, message) {
            isConverting = false;
            $convertBtn.prop('disabled', false).text('Convertir Todas las Imágenes');
            $convertSelectedBtn.prop('disabled', false).text('Convertir Seleccionadas');
            
            if (success) {
                $progressFill.css('width', '100%');
                $progressText.text(NabiWebP.strings.success);
                setTimeout(function() {
                    $progress.hide();
                    showResult('success', message);
                    loadImagesList();
                }, 1500);
            } else {
                $progress.hide();
                showResult('error', message);
            }
        }

        function showResult(type, message) {
            const icon = type === 'success' ? 'yes-alt' : (type === 'error' ? 'warning' : 'info');
            $result
                .removeClass('success error info')
                .addClass(type)
                .html('<span class="dashicons dashicons-' + icon + '"></span>' + message)
                .show();
        }
    });
})(jQuery);


