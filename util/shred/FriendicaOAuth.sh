#!/bin/bash
# Copyright (c) 2012 Fabio Comuni
# Copyright (c) 2012 Michael Nowack
# Copyright (c) 2010, 2012 Yu-Jie Lin
# 
# Permission is hereby granted, free of charge, to any person obtaining a copy of
# this software and associated documentation files (the "Software"), to deal in
# the Software without restriction, including without limitation the rights to
# use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
# of the Software, and to permit persons to whom the Software is furnished to do
# so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

FRIENDICAOAUTH_VERSION=0.1.1

F_API_VERSION="1"

# Friendica API endpoints

F_ACCOUNF_UPDATE_PROFILE_IMAGE="${redmatrix_url}/api/account/update_profile_image"
F_STATUSES_UPDATE="${redmatrix_url}/api/statuses/update"
F_STATUSES_HOME_TIMELINE="${redmatrix_url}/api/statuses/home_timeline"

F_REQUESF_TOKEN=${redmatrix_url}'/api/oauth/request_token'
F_ACCESS_TOKEN=${redmatrix_url}'/api/oauth/access_token'
F_AUTHORIZE_TOKEN=${redmatrix_url}'/api/oauth/authorize'

# Source OAuth.sh

OAuth_sh=$(which OAuth.sh)
(( $? != 0 )) && echo 'Unable to locate OAuth.sh! Make sure it is in searching PATH.' && exit 1
source "$OAuth_sh"

FO_debug () {
  # Print out all parameters, each in own line
  [[ "$FO_DEBUG" == "" ]] && return
  local t=$(date +%FT%T.%N)
  while (( $# > 0 )); do
    echo "[FO][DEBUG][$t] $1"
    shift 1
    done
  }

FO_extract_value () {
  # $1 key name
  # $2 string to find
  egrep -o "$1=[a-zA-Z0-9-]*" <<< "$2" | cut -d\= -f 2
  }


FO_init() {
  # Initialize FriendicaOAuth
  oauth_version='1.0'
  oauth_signature_method='HMAC-SHA1'
  oauth_basic_params=(
    $(OAuth_param 'oauth_consumer_key' "$oauth_consumer_key")
    $(OAuth_param 'oauth_signature_method' "$oauth_signature_method")
    $(OAuth_param 'oauth_version' "$oauth_version")
    )
  }

FO_access_token_helper () {
  # Help guide user to get access token

  local resp PIN

  # Request Token
  
  local auth_header="$(_OAuth_authorization_header 'Authorization' "$redmatrix_url/" "$oauth_consumer_key" "$oauth_consumer_secret" '' '' "$oauth_signature_method" "$oauth_version" "$(OAuth_nonce)" "$(OAuth_timestamp)" 'POST' "$F_REQUESF_TOKEN" "$(OAuth_param 'oauth_callback' 'oob')"), $(OAuth_param_quote 'oauth_callback' 'oob')"

#  echo $auth_header
#  echo $F_REQUESF_TOKEN
  
  resp=$(curl -s -d '' -H "$auth_header" "$F_REQUESF_TOKEN")
  FO_rval=$?
  (( $? != 0 )) && return $FO_rval

  local _oauth_token=$(FO_extract_value 'oauth_token' "$resp")
  local _oauth_token_secret=$(FO_extract_value 'oauth_token_secret' "$resp")

  echo 'Please go to the following link to get the PIN:'
  echo "  ${F_AUTHORIZE_TOKEN}?oauth_token=$_oauth_token"
  
  read -p 'PIN: ' PIN

  # Access Token

  local auth_header="$(_OAuth_authorization_header 'Authorization' "$redmatrix_url/" "$oauth_consumer_key" "$oauth_consumer_secret" "$_oauth_token" "$_oauth_token_secret" "$oauth_signature_method" "$oauth_version" "$(OAuth_nonce)" "$(OAuth_timestamp)" 'POST' "$F_ACCESS_TOKEN" "$(OAuth_param 'oauth_verifier' "$PIN")"), $(OAuth_param_quote 'oauth_verifier' "$PIN")"

  resp=$(curl -s -d "" -H "$auth_header" "$F_ACCESS_TOKEN")
  FO_rval=$?
  (( $? != 0 )) && return $FO_rval
  
  FO_ret=(
    $(FO_extract_value 'oauth_token' "$resp")
    $(FO_extract_value 'oauth_token_secret' "$resp")
    $(FO_extract_value 'user_id' "$resp")
    $(FO_extract_value 'screen_name' "$resp")
    )
  }

# APIs
######

FO_statuses_update () {
  # $1 format
  # $2 status
  # $3 in_reply_to_status_id
  # The followins are not implemented yet:
  # $4 lat
  # $5 long
  # $6 place_id
  # $7 display_coordinates
  local format="$1"
  [[ "$format" == "" ]] && format="xml"
  
  local params=(
    $(OAuth_param 'status' "$2")
    )
  
  params[${#params[@]}]=$(OAuth_param 'source' "shred")
  
  [[ "$3" != "" ]] && params[${#params[@]}]=$(OAuth_param 'in_reply_to_status_id' "$3") && local in_reply_to_status_id=( '--data-urlencode' "in_reply_to_status_id=$3" )
    
  
  local auth_header=$(OAuth_authorization_header 'Authorization' "$redmatrix_url" '' '' 'POST' "$F_STATUSES_UPDATE.$format" ${params[@]})
    
  
  FO_ret=$(curl -s -H "$auth_header" --data-urlencode "status=$2" --data-urlencode "source=shred" ${in_reply_to_status_id[@]} "$F_STATUSES_UPDATE.$format")

  FO_rval=$?
  return $FO_rval
  }


# gets the user home_timeline.
#
# @sets FO_ret API response
# @returns status
# @public
FO_statuses_home_timeline () {
  # $1 format
  # $2 screen_name
  # $3 count
  local format="$1"
  local screen_name="$2"
  local count="$3"
  [[ "$format" == "" ]] && format="xml"
  [[ "$count" == "" ]] && count=1

  local params=(
    $(OAuth_param 'screen_name' $screen_name)
    $(OAuth_param 'count' $count)
    )
g
  local auth_header=$(OAuth_authorization_header 'Authorization' "$redmatrix_url" '' '' 'GET' "$F_STATUSES_HOME_TIMELINE.$format" ${params[@]})

  convscreen=$(OAuth_PE "$screen_name");
  FO_ret=$(curl -s --get "${F_STATUSES_HOME_TIMELINE}.${format}" --data "screen_name=${convscreen}&count=${count}" --header "${auth_header}")
  FO_rval=$?

  return $FO_rval
  }
