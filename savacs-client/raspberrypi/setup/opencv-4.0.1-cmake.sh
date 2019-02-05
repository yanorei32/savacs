#!/bin/bash

cmake \
 -D CMAKE_BUILD_TYPE=Release \
 -D CMAKE_INSTALL_PREFIX=/usr/local \
 -D ENABLE_FAST_MATH=ON \
 -D ENABLE_NEON=ON \
 -D ENABLE_VFPV3=ON \
 -D BUILD_JAVA=OFF \
 -D BUILD_PACKAGE=OFF \
 -D BUILD_PERF_TESTS=OFF \
 -D BUILD_TESTS=OFF \
 -D BUILD_opencv_dnn=OFF \
 -D BUILD_PROTOBUF=OFF \
 -D WITH_PROTOBUF=OFF \
 -D BUILD_opencv_flann=OFF \
 -D BUILD_opencv_highgui=OFF \
 -D BUILD_opencv_features2d=OFF \
 -D BUILD_opencv_java_bindings_generator=OFF \
 -D BUILD_opencv_ml=OFF \
 -D BUILD_opencv_objdetect=OFF \
 -D BUILD_opencv_photo=OFF \
 -D BUILD_opencv_shape=OFF \
 -D BUILD_opencv_stitching=OFF \
 -D BUILD_opencv_superres=OFF \
 -D BUILD_opencv_ts=OFF \
 -D BUILD_opencv_video=OFF \
 -D BUILD_opencv_videostab=OFF \
 -D BUILD_opencv_gapi=OFF \
 -D CV_TRACE=OFF \
 -D WITH_1394=OFF \
 -D WITH_CUDA=OFF \
 -D WITH_CUBLAS=OFF \
 -D WITH_CUFFT=OFF \
 -D WITH_NVCUVID=OFF \
 -D WITH_GPHOTO2=OFF \
 -D WITH_GTK=OFF \
 -D WITH_ITT=OFF \
 -D WITH_JASPER=OFF \
 -D WITH_EIGEN=OFF \
 -D WITH_MATLAB=OFF \
 -D WITH_LAPACK=OFF \
 -D WITH_OPENCLAMDBLAS=OFF \
 -D WITH_OPENCLAMDFFT=OFF \
 -D WITH_OPENEXR=OFF \
 -D WITH_PNG=OFF \
 -D WITH_TIFF=OFF \
 -D WITH_VTK=OFF \
 -D WITH_WEBP=OFF \
 -D WITH_FFMPEG=OFF \
 -D WITH_GSTREAMER=OFF \
 -D WITH_IMGCODEC_HDR=OFF \
 -D WITH_IMGCODEC_SUNRASTER=OFF \
 -D WITH_IMGCODEC_PXM=OFF \
 -D WITH_IMGCODEC_PFM=OFF \
 -D WITH_CAROTENE=OFF \
 -D BUILD_opencv_apps=OFF \
 -D BUILD_opencv_python3=ON \
 ..

#  -D BUILD_opencv_python2=ON \


