(function($) {
    'use strict';

    $(document).ready(function() {
        // Manejo de pestañas
        $('.dn325-webp-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-pane').removeClass('active');
            $(target + '-tab').addClass('active');
        });

        // Formulario de configuración
        $('#dn325-webp-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $result = $('#dn325-webp-settings-result');
            
            $.ajax({
                url: dn325WebP.ajax_url,
                type: 'POST',
                data: {
                    action: 'dn325_webp_save_settings',
                    nonce: dn325WebP.nonce,
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

        const $convertBtn = $('#dn325-webp-convert-btn');
        const $scanBtn = $('#dn325-webp-scan-btn');
        const $progress = $('#dn325-webp-progress');
        const $progressFill = $progress.find('.dn325-webp-progress-fill');
        const $progressText = $progress.find('.dn325-webp-progress-text');
        const $progressDetails = $('#dn325-webp-progress-details');
        const $result = $('#dn325-webp-result');
        const $totalCount = $('#dn325-webp-total-count');
        const $converted = $('#dn325-webp-converted');
        const $total = $('#dn325-webp-total');

        let isConverting = false;
        let totalConverted = 0;
        let totalImages = 0;

        // Botón para actualizar conteo
        $scanBtn.on('click', function(e) {
            e.preventDefault();
            updateImageCount();
        });

        // Botón para convertir todas las imágenes
        $convertBtn.on('click', function(e) {
            e.preventDefault();
            
            if (isConverting) {
                return;
            }

            if (!confirm(dn325WebP.strings.confirm_convert)) {
                return;
            }

            startConversion();
        });

        /**
         * Actualiza el conteo de imágenes
         */
        function updateImageCount() {
            $.ajax({
                url: dn325WebP.ajax_url,
                type: 'POST',
                data: {
                    action: 'dn325_webp_get_count',
                    nonce: dn325WebP.nonce
                },
                beforeSend: function() {
                    $scanBtn.prop('disabled', true).text('Escaneando...');
                },
                success: function(response) {
                    if (response.success) {
                        $totalCount.text(response.data.count);
                        showResult('info', 'Conteo actualizado: ' + response.data.count + ' imágenes disponibles');
                    } else {
                        showResult('error', response.data.message || 'Error al obtener el conteo');
                    }
                },
                error: function() {
                    showResult('error', 'Error de conexión');
                },
                complete: function() {
                    $scanBtn.prop('disabled', false).text('Actualizar Conteo');
                }
            });
        }

        /**
         * Inicia la conversión de todas las imágenes
         */
        function startConversion() {
            isConverting = true;
            totalConverted = 0;
            totalImages = parseInt($totalCount.text()) || 0;

            if (totalImages === 0) {
                isConverting = false;
                showResult('error', dn325WebP.strings.no_images);
                return;
            }

            $convertBtn.prop('disabled', true).text('Convirtiendo...');
            $progress.show();
            $result.hide();
            $progressDetails.html('');
            $progressFill.css('width', '5%');
            $progressText.text('Iniciando conversión...');
            $total.text(totalImages);
            $converted.text('0');

            convertBatch(0);
        }

        /**
         * Convierte un lote de imágenes
         */
        function convertBatch(offset) {
            $.ajax({
                url: dn325WebP.ajax_url,
                type: 'POST',
                timeout: 300000,
                data: {
                    action: 'dn325_webp_convert_all',
                    nonce: dn325WebP.nonce,
                    batch_size: 20,
                    offset: offset,
                    skip_references: true
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        totalConverted += data.converted;

                        // Actualizar progreso
                        const percentage = totalImages > 0 ? Math.min(95, (totalConverted / totalImages) * 100) : 0;
                        $progressFill.css('width', percentage + '%');
                        $converted.text(totalConverted);
                        $progressText.text(
                            data.message || dn325WebP.strings.converting
                        );

                        // Actualizar detalles de progreso
                        let detailsHtml = $progressDetails.html();
                        if (data.results && data.results.length > 0) {
                            data.results.forEach(function(result) {
                                if (!result.success) {
                                    detailsHtml += '<div class="progress-item">Error en imagen ID ' + result.id + ': ' + result.message + '</div>';
                                } else {
                                    detailsHtml += '<div class="progress-item">Imagen ID ' + result.id + ' convertida exitosamente</div>';
                                }
                            });
                        } else {
                            detailsHtml += '<div class="progress-item">Procesadas ' + (data.processed || 0) + ' imágenes. Convertidas: ' + data.converted + ', Errores: ' + (data.errors || 0) + '</div>';
                        }
                        $progressDetails.html(detailsHtml);
                        if (detailsHtml) {
                            $progressDetails.scrollTop($progressDetails[0].scrollHeight);
                        }

                        if (data.completed) {
                            finishConversion(true, dn325WebP.strings.success + '. Total convertidas: ' + totalConverted);
                        } else if (data.has_more) {
                            const delay = data.timeout ? 1000 : 300;
                            setTimeout(function() {
                                convertBatch(data.offset);
                            }, delay);
                        } else {
                            finishConversion(true, dn325WebP.strings.success + '. Total convertidas: ' + totalConverted);
                        }
                    } else {
                        finishConversion(false, response.data.message || dn325WebP.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout' || status === 'error') {
                        const currentOffset = offset;
                        showResult('info', 'Timeout detectado. Reintentando desde la imagen ' + (currentOffset + 1) + '...');
                        setTimeout(function() {
                            convertBatch(currentOffset);
                        }, 2000);
                    } else {
                        isConverting = false;
                        $convertBtn.prop('disabled', false).text('Convertir Todas las Imágenes');
                        finishConversion(false, 'Error de conexión durante la conversión: ' + error);
                    }
                }
            });
        }

        /**
         * Finaliza la conversión
         */
        function finishConversion(success, message) {
            if (success) {
                isConverting = false;
                $convertBtn.prop('disabled', false).text('Convertir Todas las Imágenes');
                $progressFill.css('width', '100%');
                $progressText.text(dn325WebP.strings.success);
                setTimeout(function() {
                    $progress.hide();
                    showResult('success', message);
                }, 1000);
                updateImageCount();
            } else {
                if (message && (message.indexOf('timeout') === -1 && message.indexOf('Reintentando') === -1)) {
                    isConverting = false;
                    $convertBtn.prop('disabled', false).text('Convertir Todas las Imágenes');
                    $progress.hide();
                }
                showResult('error', message);
            }
        }

        /**
         * Muestra un mensaje de resultado
         */
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
