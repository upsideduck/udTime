#!/usr/bin/env bash
# Build the udTime Docker images (udtime2-backend, udtime2-frontend) and optionally push
# them to Docker Hub. Every image is tagged `latest` and `<YYYYMMDD_HHMMSS>`.
#
# Interactive:      ./build-images.sh
# Non-interactive:  ./build-images.sh [backend|frontend|both] [--push|--no-push] [--yes]
#
# Docker Hub credentials and settings are read from docker.env (see docker.env.example).

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

bold() { printf '\033[1m%s\033[0m\n' "$*"; }
info() { printf '\033[36m==>\033[0m %s\n' "$*"; }
fail() { printf '\033[31mError:\033[0m %s\n' "$*" >&2; exit 1; }

# --- settings ---------------------------------------------------------------------------

if [[ -f docker.env ]]; then
  set -a
  # shellcheck disable=SC1091
  source docker.env
  set +a
fi
UDTIME2_DOCKERHUB_USERNAME="${UDTIME2_DOCKERHUB_USERNAME:-}"
UDTIME2_DOCKERHUB_TOKEN="${UDTIME2_DOCKERHUB_TOKEN:-}"
UDTIME2_DOCKERHUB_NAMESPACE="${UDTIME2_DOCKERHUB_NAMESPACE:-$UDTIME2_DOCKERHUB_USERNAME}"
[[ "$UDTIME2_DOCKERHUB_NAMESPACE" == "your-dockerhub-user" ]] && UDTIME2_DOCKERHUB_NAMESPACE=""  # template not filled in
UDTIME2_DOCKER_PLATFORMS="${UDTIME2_DOCKER_PLATFORMS:-linux/amd64}"

command -v docker >/dev/null || fail "docker is not installed"
docker info >/dev/null 2>&1 || fail "the Docker daemon is not running"

# --- arguments / questions --------------------------------------------------------------

TARGET="" PUSH="" ASSUME_YES=""
for arg in "$@"; do
  case "$arg" in
    backend|frontend|both) TARGET="$arg" ;;
    --push) PUSH="yes" ;;
    --no-push) PUSH="no" ;;
    -y|--yes) ASSUME_YES="yes" ;;
    -h|--help) sed -n '2,8p' "$0"; exit 0 ;;
    *) fail "unknown argument: $arg" ;;
  esac
done

if [[ -z "$TARGET" ]]; then
  bold "Which image(s) do you want to build?"
  echo "  1) udtime2-backend"
  echo "  2) udtime2-frontend"
  echo "  3) both"
  read -r -p "Choose [1-3] (default 3): " choice
  case "${choice:-3}" in
    1) TARGET="backend" ;;
    2) TARGET="frontend" ;;
    3) TARGET="both" ;;
    *) fail "invalid choice: $choice" ;;
  esac
fi

if [[ -z "$PUSH" ]]; then
  read -r -p "Upload to hub.docker.com? [y/N]: " answer
  case "${answer:-n}" in
    y|Y|yes|YES) PUSH="yes" ;;
    *) PUSH="no" ;;
  esac
fi

case "$TARGET" in
  backend) COMPONENTS="backend" ;;
  frontend) COMPONENTS="frontend" ;;
  both) COMPONENTS="backend frontend" ;;
esac

if [[ "$PUSH" == "yes" ]]; then
  [[ -n "$UDTIME2_DOCKERHUB_USERNAME" && -n "$UDTIME2_DOCKERHUB_TOKEN" ]] \
    || fail "UDTIME2_DOCKERHUB_USERNAME and UDTIME2_DOCKERHUB_TOKEN must be set in docker.env to upload"
  [[ "$UDTIME2_DOCKERHUB_TOKEN" != dckr_pat_xxxx* ]] || fail "docker.env still contains the example token"
fi

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
REVISION="$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
CREATED="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

image_name() {
  if [[ -n "$UDTIME2_DOCKERHUB_NAMESPACE" ]]; then
    echo "$UDTIME2_DOCKERHUB_NAMESPACE/udtime2-$1"
  else
    echo "udtime2-$1"
  fi
}

# --- summary ----------------------------------------------------------------------------

echo
bold "Build plan"
for component in $COMPONENTS; do
  echo "  $(image_name "$component"):latest"
  echo "  $(image_name "$component"):$TIMESTAMP"
done
if [[ "$PUSH" == "yes" ]]; then
  echo "  Upload:    yes, to hub.docker.com as $UDTIME2_DOCKERHUB_USERNAME"
  echo "  Platforms: $UDTIME2_DOCKER_PLATFORMS"
else
  echo "  Upload:    no (loaded into the local Docker image store)"
  echo "  Platform:  local ($(docker version --format '{{.Server.Os}}/{{.Server.Arch}}'))"
fi
echo "  Revision:  $REVISION"
if [[ -n "$(git status --porcelain 2>/dev/null)" ]]; then
  echo "  Note:      the working tree has uncommitted changes; they are included in the images"
fi
echo

if [[ -z "$ASSUME_YES" ]]; then
  read -r -p "Continue? [Y/n]: " confirm
  case "${confirm:-y}" in
    y|Y|yes|YES) ;;
    *) echo "Aborted."; exit 0 ;;
  esac
fi

# --- build ------------------------------------------------------------------------------

if [[ "$PUSH" == "yes" ]]; then
  info "Logging in to Docker Hub as $UDTIME2_DOCKERHUB_USERNAME"
  printf '%s' "$UDTIME2_DOCKERHUB_TOKEN" | docker login --username "$UDTIME2_DOCKERHUB_USERNAME" --password-stdin >/dev/null

  # Cross-platform builds with --push need a docker-container builder
  if ! docker buildx inspect udtime-builder >/dev/null 2>&1; then
    info "Creating buildx builder 'udtime-builder'"
    docker buildx create --name udtime-builder --driver docker-container >/dev/null
  fi
  BUILD_ARGS=(--builder udtime-builder --platform "$UDTIME2_DOCKER_PLATFORMS" --push)
else
  BUILD_ARGS=(--load)
fi

for component in $COMPONENTS; do
  image="$(image_name "$component")"
  info "Building $image ($component/)"
  docker buildx build "${BUILD_ARGS[@]}" \
    --tag "$image:latest" \
    --tag "$image:$TIMESTAMP" \
    --label "org.opencontainers.image.title=udtime2-$component" \
    --label "org.opencontainers.image.version=$TIMESTAMP" \
    --label "org.opencontainers.image.revision=$REVISION" \
    --label "org.opencontainers.image.created=$CREATED" \
    --build-arg "BUILD_DATE=$CREATED" \
    "$component"
done

echo
bold "Done"
for component in $COMPONENTS; do
  image="$(image_name "$component")"
  if [[ "$PUSH" == "yes" ]]; then
    echo "  pushed  $image:latest, $image:$TIMESTAMP"
  else
    echo "  built   $image:latest, $image:$TIMESTAMP"
  fi
done
