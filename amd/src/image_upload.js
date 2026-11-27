// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Optional personal screenshot reduction before upload. Existing files stay unchanged.
 * @module local_sidenotes/image_upload
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const base64 = file => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result).split(',')[1]);
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(file);
});

let reductionEnabled;

/** Bootstrap once; a later sidebar initialisation must not undo a preference just saved in the overview. */
export const configure = (enabled, initial = false) => {
    if (!initial || reductionEnabled === undefined) reductionEnabled = !!enabled;
};

/** Keep GIF animation and use the original whenever the reduction is unavailable or larger. */
export const prepare = async(file, reduce = !!reductionEnabled) => {
    let image = file;
    if (reduce && ['image/png', 'image/jpeg', 'image/webp'].includes(file.type) && window.createImageBitmap) {
        let bitmap;
        try {
            bitmap = await createImageBitmap(file);
            const scale = Math.min(1, 2048 / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, file.type, .85));
            if (blob && blob.size < file.size && blob.type === file.type) image = blob;
        } catch (error) {
            // The unchanged original still goes through all server-side image and quota checks.
        } finally {
            bitmap?.close();
        }
    }
    return {filename: file.name || 'screenshot.png', mimetype: image.type, data: await base64(image)};
};
