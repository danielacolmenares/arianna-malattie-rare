FROM debian:bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends build-essential ca-certificates cmake curl ffmpeg git \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /opt
RUN git clone --filter=blob:none https://github.com/ggml-org/whisper.cpp.git \
    && cd whisper.cpp \
    && git checkout 1d549b3cecc2d98d76d4ddc2edca0d1512f5d7a0 \
    && cmake -S . -B build -DCMAKE_BUILD_TYPE=Release -DGGML_NATIVE=OFF \
    && cmake --build build --target whisper-server -j 4

ENV LD_LIBRARY_PATH=/opt/whisper.cpp/build/src:/opt/whisper.cpp/build/ggml/src
ENV PATH=/opt/whisper.cpp/build/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
ENTRYPOINT ["bash", "-c"]
