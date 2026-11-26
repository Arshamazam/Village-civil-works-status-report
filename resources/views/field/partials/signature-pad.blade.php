<div class="space-y-3 border-t border-slate-100 pt-5">
    <div class="flex items-center justify-between gap-3">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
            Digital Signature <span class="text-rose-500">*</span>
        </label>
        <button type="button" class="text-xs font-bold text-emerald-700 hover:underline" data-clear-signature>
            Clear
        </button>
    </div>
    <p class="text-xs text-slate-500">Sign below with your finger, stylus, or mouse before submitting.</p>
    <canvas width="900" height="360" tabindex="0" role="img" aria-label="Draw your digital signature"
            class="block w-full rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 touch-none"
            data-signature-canvas></canvas>
    <input type="file" name="signature" accept="image/png" class="hidden" data-signature-upload>
    <p class="text-xs text-rose-600" data-signature-error role="alert" hidden></p>
</div>

@pushOnce('scripts')
<script>
function initializeSignaturePad(form) {
    if (form.dataset.signaturePadInitialized) {
        return;
    }

    form.dataset.signaturePadInitialized = 'true';

    const canvas = form.querySelector('[data-signature-canvas]');
    const signatureUpload = form.querySelector('[data-signature-upload]');
    const signatureError = form.querySelector('[data-signature-error]');
    const clearSignature = form.querySelector('[data-clear-signature]');
    const context = canvas.getContext('2d');
    let isDrawing = false;
    let hasSignature = false;
    let signatureReady = false;
    let isPreparingSignature = false;

    context.strokeStyle = '#0f172a';
    context.lineWidth = 5;
    context.lineCap = 'round';
    context.lineJoin = 'round';

    const pointFor = (event) => {
        const bounds = canvas.getBoundingClientRect();

        return {
            x: (event.clientX - bounds.left) * canvas.width / bounds.width,
            y: (event.clientY - bounds.top) * canvas.height / bounds.height,
        };
    };

    canvas.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        isDrawing = true;
        signatureReady = false;
        signatureUpload.value = '';
        signatureError.hidden = true;
        canvas.setPointerCapture(event.pointerId);
        const point = pointFor(event);
        context.beginPath();
        context.moveTo(point.x, point.y);
    });

    canvas.addEventListener('pointermove', (event) => {
        if (!isDrawing) {
            return;
        }

        const point = pointFor(event);
        context.lineTo(point.x, point.y);
        context.stroke();
        hasSignature = true;
    });

    const stopDrawing = () => {
        isDrawing = false;
    };

    canvas.addEventListener('pointerup', stopDrawing);
    canvas.addEventListener('pointercancel', stopDrawing);

    clearSignature.addEventListener('click', () => {
        context.clearRect(0, 0, canvas.width, canvas.height);
        signatureUpload.value = '';
        hasSignature = false;
        signatureReady = false;
        signatureError.hidden = true;
    });

    form.addEventListener('submit', (event) => {
        if (!hasSignature) {
            event.preventDefault();
            signatureError.textContent = 'Please draw your digital signature before submitting.';
            signatureError.hidden = false;
            canvas.focus();
            canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });

            return;
        }

        if (signatureReady) {
            return;
        }

        event.preventDefault();
        if (isPreparingSignature) {
            return;
        }

        isPreparingSignature = true;
        canvas.toBlob((blob) => {
            if (!blob) {
                isPreparingSignature = false;
                signatureError.textContent = 'Unable to prepare the signature. Please sign again.';
                signatureError.hidden = false;

                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'signature.png', { type: 'image/png' }));
            signatureUpload.files = transfer.files;
            signatureReady = true;

            if (event.submitter instanceof HTMLElement && event.submitter.form === form) {
                form.requestSubmit(event.submitter);
            } else {
                form.requestSubmit();
            }
        }, 'image/png');
    });
}
</script>
@endPushOnce
