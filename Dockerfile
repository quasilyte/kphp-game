FROM debian:10 AS builder

ENV DEBIAN_FRONTEND noninteractive

# https://vkcom.github.io/kphp/kphp-internals/developing-and-extending-kphp/compiling-kphp-from-sources.html
RUN apt-get update \
	&& apt-get install -y --no-install-recommends apt-utils ca-certificates gnupg wget \
	&& echo "deb https://deb.debian.org/debian buster-backports main" >> /etc/apt/sources.list \
	&& wget -qO - https://repo.vkpartner.ru/GPG-KEY.pub | apt-key add - \
	&& echo "deb https://repo.vkpartner.ru/kphp-buster/ buster main" >> /etc/apt/sources.list  \
	&& wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
	&& echo "deb https://packages.sury.org/php/ buster main" >> /etc/apt/sources.list.d/php.list \
	&& apt-get update \
	&& apt-get upgrade -y \
	&& apt install -y git cmake-data=3.16* cmake=3.16* make g++ gperf python3-minimal python3-jsonschema \
        curl-kphp-vk libuber-h3-dev kphp-timelib libfmt-dev libgmock-dev libre2-dev libpcre3-dev \
        libzstd-dev libyaml-cpp-dev libmsgpack-dev libnghttp2-dev zlib1g-dev php7.4-dev \
        bison \
    \
    && cd /tmp \
    && git clone --depth 1 --branch v1.10.x https://github.com/google/googletest.git \
    && cd googletest \
    && cmake . \
    && make -j$(nproc) \
    && make install \
    \
    && cd /tmp \
    && git clone https://github.com/VKCOM/kphp.git \
	&& cd kphp \
	&& git fetch -a \
	&& git checkout quasilyte/ffi \
    && mkdir build \
	&& cd build \
	&& cmake .. \
	&& make -j$(nproc)

FROM debian:10-slim AS runner

ARG SRC=/tmp/kphp
ARG DST=/opt/kphp

WORKDIR $DST
ENV PATH "${DST}/objs/bin:${PATH}"

ENV KPHP_PATH $DST

RUN apt-get update \
	&& apt-get install -y --no-install-recommends apt-utils ca-certificates gnupg wget \
	&& echo "deb https://deb.debian.org/debian buster-backports main" >> /etc/apt/sources.list \
	&& wget -qO - https://repo.vkpartner.ru/GPG-KEY.pub | apt-key add - \
	&& echo "deb https://repo.vkpartner.ru/kphp-buster/ buster main" >> /etc/apt/sources.list  \
	&& apt-get update \
	&& apt-get upgrade -y \
    && apt-get install -y libuber-h3-dev kphp-timelib libssl-dev g++ libre2-dev libmsgpack-dev libpcre3-dev \
      libyaml-cpp-dev zlib1g-dev libzstd-dev libnghttp2-dev \
    && rm -rf /var/lib/apt/lists/*

COPY --from=builder /opt/curl7600/include /opt/curl7600/include
COPY --from=builder /opt/curl7600/lib /opt/curl7600/lib

COPY --from=builder $SRC/objs/bin/kphp2cpp $DST/objs/bin/kphp2cpp
COPY --from=builder $SRC/objs/generated $DST/objs/generated
COPY --from=builder $SRC/objs/flex/libvk-flex-data.a $DST/objs/flex/
COPY --from=builder $SRC/objs/libkphp-full-runtime.a $DST/objs
COPY --from=builder $SRC/objs/php_lib_version.sha256 $DST/objs
COPY --from=builder $SRC/functions.txt $DST
COPY --from=builder $SRC/functions_ffi.txt $DST
COPY --from=builder $SRC/functions_spl.txt $DST
COPY --from=builder $SRC/functions_uberh3.txt $DST
COPY --from=builder $SRC/runtime $DST/runtime
COPY --from=builder $SRC/common $DST/common
COPY --from=builder $SRC/server $DST/server

ENTRYPOINT ["kphp2cpp"]
