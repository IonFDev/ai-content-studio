import './bootstrap';
import 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    initSceneReordering();
    initCopyButtons();
});

function initSceneReordering() {
    const sceneList = document.getElementById('scene-list');
    const reorderForm = document.getElementById('scene-reorder-form');
    const ordersContainer = document.getElementById('scene-orders');

    if (!sceneList || !reorderForm || !ordersContainer) {
        return;
    }

    let draggedScene = null;

    sceneList.querySelectorAll('.scene-card').forEach((card) => {
        card.setAttribute('draggable', 'true');

        card.addEventListener('dragstart', (event) => {
            draggedScene = card;
            card.classList.add('opacity-50');

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.sceneId);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('opacity-50');
            draggedScene = null;

            updateSceneNumbers();
        });

        card.addEventListener('dragover', (event) => {
            event.preventDefault();

            if (!draggedScene || draggedScene === card) {
                return;
            }

            const rect = card.getBoundingClientRect();
            const middle = rect.top + rect.height / 2;

            if (event.clientY < middle) {
                sceneList.insertBefore(draggedScene, card);
            } else {
                sceneList.insertBefore(draggedScene, card.nextSibling);
            }
        });
    });

    reorderForm.addEventListener('submit', () => {
        ordersContainer.innerHTML = '';

        sceneList.querySelectorAll('.scene-card').forEach((card) => {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = 'orders[]';
            input.value = card.dataset.sceneId;

            ordersContainer.appendChild(input);
        });
    });

    updateSceneNumbers();
}

function updateSceneNumbers() {
    const sceneList = document.getElementById('scene-list');

    if (!sceneList) {
        return;
    }

    sceneList.querySelectorAll('.scene-card').forEach((card, index) => {
        const number = String(index + 1).padStart(2, '0');
        const numberElement = card.querySelector('.scene-number');

        if (numberElement) {
            numberElement.textContent = `SCENE ${number}`;
        }
    });
}

function initCopyButtons() {
    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(
                button.dataset.copyTarget
            );

            if (!target) {
                return;
            }

            try {
                await navigator.clipboard.writeText(target.value);

                const originalText = button.textContent;
                button.textContent = 'Copiado';

                setTimeout(() => {
                    button.textContent = originalText;
                }, 1500);
            } catch (error) {
                console.error('No se pudo copiar el contenido:', error);
            }
        });
    });
}
