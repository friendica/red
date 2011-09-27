sizes="10 16 22 48"

for s in $sizes
do
 echo "=[ ${s}x${s} ]===="
 [ -d $s ] || mkdir $s
 for f in *.png
 do
    convert $f -resize ${s}x${s} $s/$f
    echo -n "#"
 done
 echo
done
echo "Ok."
