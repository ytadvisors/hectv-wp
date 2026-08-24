def nonempty_string:
  type == "string" and length > 0;

def media_key:
  sub("^https://prd-hectv-wp-media[.]s3[.]us-east-2[.]amazonaws[.]com"; "")
  | sub("^https://prd-hectv-wp-media[.]s3-us-east-2[.]amazonaws[.]com"; "")
  | sub("^https://prd-hectv-wp-media[.]s3[.]amazonaws[.]com"; "")
  | sub("^https://s3-us-east-2[.]amazonaws[.]com/prd-hectv-wp-media"; "")
  | sub("^https://s3[.]us-east-2[.]amazonaws[.]com/prd-hectv-wp-media"; "");

def media_dir:
  media_key | split("/") | .[0:-1] | join("/");

def media_images:
  .data.posts.nodes[]?.postDetails?
  | (.postHeader?, .videoImage?, .postHero?)
  | select(type == "object");

(
  [
    media_images
    | select(((.medium? | nonempty_string) or (.large? | nonempty_string)) | not)
  ]
  | length == 0
)
and
(
  [
    media_images
    | . as $image
    | select($image.mediaItemUrl? | nonempty_string)
    | ($image.medium?, $image.large?)
    | select(nonempty_string)
    | select(media_dir != ($image.mediaItemUrl | media_dir))
  ]
  | length == 0
)
