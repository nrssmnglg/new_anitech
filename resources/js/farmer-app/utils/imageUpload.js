export async function prepareUploadFile(file, options = {}) {
    if (!(file instanceof File)) {
        return { file, compressed: false };
    }

    if (!file.type.startsWith('image/')) {
        return {
            file,
            compressed: false,
            originalSize: file.size,
            finalSize: file.size,
        };
    }

    // Keep formats like HEIC/HEIF untouched. Browsers do not reliably re-encode them.
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        return {
            file,
            compressed: false,
            originalSize: file.size,
            finalSize: file.size,
        };
    }

    const compressed = await compressImageFile(file, options);

    return {
        file: compressed,
        compressed: compressed.size !== file.size,
        originalSize: file.size,
        finalSize: compressed.size,
    };
}

async function compressImageFile(file, options = {}) {
    const {
        maxWidth = 1800,
        maxHeight = 1800,
        quality = 0.82,
        minBytesToCompress = 1.5 * 1024 * 1024,
    } = options;

    if (file.size <= minBytesToCompress) {
        return file;
    }

    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, maxWidth / bitmap.width, maxHeight / bitmap.height);
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d');

    if (!context) {
        return file;
    }

    context.drawImage(bitmap, 0, 0, width, height);

    const targetMime = file.type === 'image/png' ? 'image/jpeg' : file.type;
    const blob = await new Promise((resolve) => {
        canvas.toBlob(resolve, targetMime, quality);
    });

    if (!(blob instanceof Blob) || blob.size >= file.size) {
        return file;
    }

    return new File([blob], normalizeFileName(file.name, targetMime), {
        type: targetMime,
        lastModified: Date.now(),
    });
}

function normalizeFileName(name, mime) {
    const base = name.replace(/\.[^.]+$/, '');

    if (mime === 'image/jpeg') {
        return `${base}.jpg`;
    }

    if (mime === 'image/png') {
        return `${base}.png`;
    }

    return name;
}
