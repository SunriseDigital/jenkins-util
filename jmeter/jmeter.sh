#!/bin/sh
while getopts "j:o:" flag; do
  case $flag in
    \?) OPT_ERROR=1; break;;
    j) jmeter_home="$OPTARG";;
    o) jmeter_options="${OPTARG} ";;
  esac
done

shift $(( $OPTIND - 1 ))

#最初の引数がtarget_dir
target_dir="$1"

if [ $OPT_ERROR ]; then      # option error
  echo >&2 "usage: $0 [-ab] [-c arg1] [-d arg2] ..."
  exit 1
fi

hasError=0
find ${target_dir} -name "*.jmx" | while read jmx ; do
  timekey=`date +%s%N`
  hashkey=`head /dev/urandom -c128 | sha1sum | head -c12`
  log_file=`printf "/tmp/ju-jmeter-%s-%s.log" "${timekey}" "${hashkey}"`

  if [ -e ${log_file} ]; then
    echo >&2 "Temp file ${log_file} is exists."
    exit 1
  fi

  cmd="/bin/sh ${jmeter_home}/bin/jmeter -n -Jlogname=\"${log_file}\" ${jmeter_options}-t ${jmx}"
  echo ${cmd}

  eval ${cmd}\
  && {
    if [ -e ${log_file} ]; then
      hasError=1
      cat ${log_file}
      rm ${log_file}
      exit 1
    fi
  }
done
