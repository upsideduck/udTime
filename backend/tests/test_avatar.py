import io
import os

from PIL import Image

from app.services.users import AVATAR_SIZE, STORED_AVATAR_LIMIT

API = "/api/v1"


def _image(fmt: str, size: tuple[int, int], mode: str = "RGB", exif_orientation: int | None = None) -> bytes:
    # Random pixels compress badly, so the result is close to the worst case
    image = Image.frombytes(mode, size, os.urandom(size[0] * size[1] * len(mode)))
    out = io.BytesIO()
    kwargs = {}
    if exif_orientation:
        exif = Image.Exif()
        exif[0x0112] = exif_orientation
        kwargs["exif"] = exif
    image.save(out, fmt, **kwargs)
    return out.getvalue()


def _upload(client, auth, data: bytes, name="photo"):
    return client.put(f"{API}/users/me/avatar", files={"file": (name, data)}, headers=auth)


def _stored(client, auth) -> tuple[Image.Image, int, str]:
    res = client.get(f"{API}/users/me/avatar", headers=auth)
    assert res.status_code == 200
    return Image.open(io.BytesIO(res.content)), len(res.content), res.headers["content-type"]


def test_large_photo_is_cropped_and_shrunk(client, user, auth):
    data = _image("JPEG", (3000, 2000))
    assert len(data) > STORED_AVATAR_LIMIT
    assert _upload(client, auth, data).status_code == 200
    image, size, media_type = _stored(client, auth)
    assert image.size == (AVATAR_SIZE, AVATAR_SIZE)
    assert image.format == "JPEG" and media_type == "image/jpeg"
    assert size <= STORED_AVATAR_LIMIT


def test_exif_rotation_is_applied(client, user, auth):
    # Orientation 6 = rotate 90°: a landscape file shown as portrait
    assert _upload(client, auth, _image("JPEG", (400, 200), exif_orientation=6)).status_code == 200
    image, _, _ = _stored(client, auth)
    assert image.size == (200, 200)  # not upscaled; cropped to the short side


def test_transparent_png_stays_png(client, user, auth):
    assert _upload(client, auth, _image("PNG", (100, 80), mode="RGBA")).status_code == 200
    image, size, media_type = _stored(client, auth)
    assert image.format == "PNG" and image.mode == "RGBA" and media_type == "image/png"
    assert image.size == (80, 80) and size <= STORED_AVATAR_LIMIT


def test_large_transparent_png_fits_the_column(client, user, auth):
    assert _upload(client, auth, _image("PNG", (1000, 1000), mode="RGBA")).status_code == 200
    _, size, _ = _stored(client, auth)
    assert size <= STORED_AVATAR_LIMIT


def test_webp_is_accepted(client, user, auth):
    assert _upload(client, auth, _image("WEBP", (600, 600))).status_code == 200
    image, _, _ = _stored(client, auth)
    assert image.size == (AVATAR_SIZE, AVATAR_SIZE)


def test_non_image_is_refused(client, user, auth):
    res = _upload(client, auth, b"<svg xmlns='http://www.w3.org/2000/svg'/>")
    assert res.status_code == 400
    assert "not a supported image" in res.json()["detail"]
    assert client.get(f"{API}/users/me/avatar", headers=auth).status_code == 404


def test_decompression_bomb_is_refused(client, user, auth):
    # A tiny file that claims a huge canvas
    out = io.BytesIO()
    Image.new("1", (10_000, 10_000)).save(out, "PNG")
    assert len(out.getvalue()) < 100_000
    assert _upload(client, auth, out.getvalue()).status_code == 400
