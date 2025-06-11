{{-- resources/views/components/backstage/scripts.blade.php --}}

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Performance Optimierungen
    let clickTimeout = null;
    let voucherClickTimeout = {};

    // VERBESSERTE Spam-Click Prevention
    document.addEventListener('click', function(e) {
        try {
            // Voucher Button Protection
            if (e.target.matches('[wire\\:click*="issueVouchers"]')) {
                const wireClick = e.target.getAttribute('wire:click');
                const personId = wireClick ? wireClick.match(/issueVouchers\((\d+)/)?.[1] : null;

                if (personId && voucherClickTimeout[personId]) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                if (personId) {
                    voucherClickTimeout[personId] = true;
                    setTimeout(() => {
                        delete voucherClickTimeout[personId];
                    }, 3000);
                }
            }

            // Allgemeine Button Protection
            const targets = ['[wire\\:click*="togglePresence"]', '[wire\\:click*="toggleGuestPresence"]'];
            const isTargetElement = targets.some(selector =>
                e.target.matches(selector) || e.target.closest(selector)
            );

            if (isTargetElement) {
                if (clickTimeout) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                clickTimeout = setTimeout(() => {
                    clickTimeout = null;
                }, 1000);
            }
        } catch (error) {
            console.error('Click handler error:', error);
        }
    });

    // Loading States
    window.addEventListener('livewire:request', () => {
        document.body.style.cursor = 'wait';
    });

    window.addEventListener('livewire:response', () => {
        document.body.style.cursor = 'default';
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        try {
            const searchInput = document.getElementById('search-input');
            const bandSearchInput = document.getElementById('band-search-input');

            if (e.key === 'Escape' && e.target.matches('input')) {
                if (e.target === searchInput && searchInput.value.trim() !== '') {
                    e.preventDefault();
                    const component = Livewire.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
                    if (component) component.call('clearSearch');
                }
                if (e.target === bandSearchInput && bandSearchInput.value.trim() !== '') {
                    e.preventDefault();
                    const component = Livewire.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
                    if (component) component.call('clearBandSearch');
                }
            }

            if (e.key === 'Escape' && !e.target.matches('input')) {
                const component = Livewire.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
                if (component) component.call('clearAllSearches');
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                if (searchInput) searchInput.focus();
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                if (bandSearchInput) bandSearchInput.focus();
            }
        } catch (error) {
            console.error('Keyboard shortcut error:', error);
        }
    });

    // LocalStorage
    try {
        const savedStageId = localStorage.getItem('backstage_purchase_stage_id');
        if (savedStageId && savedStageId !== 'null') {
            const livewireComponent = Livewire.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
            if (livewireComponent) {
                livewireComponent.set('purchaseStageId', parseInt(savedStageId));
            }
        }
    } catch (error) {
        console.error('LocalStorage error:', error);
    }

    // Livewire Events
    document.addEventListener('livewire:initialized', () => {
        try {
            const component = Livewire.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
            if (!component) return;

            component.on('search-cleared', () => {
                const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.500ms="search"]');
                const bandSearchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.500ms="bandSearch"]');

                if (searchInput) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                if (bandSearchInput) {
                    bandSearchInput.value = '';
                    bandSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            component.on('stage-selected', (stageId) => {
                try {
                    if (stageId && stageId !== null) {
                        localStorage.setItem('backstage_purchase_stage_id', stageId);
                    } else {
                        localStorage.removeItem('backstage_purchase_stage_id');
                    }
                } catch (error) {
                    console.error('Stage selection storage error:', error);
                }
            });

            component.on('voucher-issued', (data) => {
                try {
                    const voucherButtons = document.querySelectorAll(`[wire\\:click*="issueVouchers(${data.personId}"]`);
                    voucherButtons.forEach(button => {
                        button.disabled = false;
                        button.classList.remove('opacity-50');

                        if (data.remainingVouchers <= 0) {
                            const parentDiv = button.closest('.space-y-2, .flex, .grid');
                            if (parentDiv) {
                                parentDiv.style.display = 'none';
                            }
                        }
                    });
                } catch (error) {
                    console.error('Voucher issued event error:', error);
                }
            });

        } catch (error) {
            console.error('Livewire initialization error:', error);
        }
    });
});
</script>
