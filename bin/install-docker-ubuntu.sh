#!/usr/bin/env bash
# Installation depuis le dépôt officiel Docker, sans retirer les paquets existants.
set -euo pipefail
if [[ "$EUID" -ne 0 ]]; then
    echo 'Exécuter dans votre terminal : sudo bash bin/install-docker-ubuntu.sh' >&2
    exit 1
fi
source /etc/os-release
if [[ "$ID" != ubuntu ]]; then
    echo 'Ce script est réservé à Ubuntu. Voir https://docs.docker.com/engine/install/' >&2
    exit 1
fi
for package in docker.io docker-compose docker-compose-v2 docker-doc docker-buildx podman-docker containerd runc; do
    if dpkg-query -W -f='${Status}' "$package" 2>/dev/null | grep -q 'install ok installed'; then
        echo "Paquet potentiellement incompatible : $package. Suivre la documentation officielle avant installation." >&2
        exit 1
    fi
done
apt-get update
apt-get install -y ca-certificates curl
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
architecture="$(dpkg --print-architecture)"
release="${UBUNTU_CODENAME:-$VERSION_CODENAME}"
cat > /etc/apt/sources.list.d/docker.sources <<SOURCES
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $release
Components: stable
Architectures: $architecture
Signed-By: /etc/apt/keyrings/docker.asc
SOURCES
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker
docker version
docker compose version
echo 'Docker installé. Utiliser sudo docker si nécessaire ; aucun accès au groupe docker ajouté automatiquement.'
