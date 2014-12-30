# Zot - A High Level Overview

Here's a high level description of how zot works.

In this example, "Indigo" is going to send a public message from his website at "podunk.edu". "Nickordo" is a recipient on another site ("example.com").


Indigo first posts his message at podunk.edu. podunk.edu looks up who should receive the message and finds Nickordo. Nickordo usually posts from example.com so we add that destination to our list of recipients. We may also add other destinations for nickordo and anybody else that is following Indigo's posts.  

In this example we find that we only have one known recipient at one known location. 

We send a packet to example.com:

    {
      "type":"notify",
      "sender":{
        "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
        "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q",
        "url":"http:\/\/podunk.edu",
        "url_sig":"T8Bp7j5DHHhQDCFcAHXfuhUfGk2P3inPbImwaXXF1xJd3TGgluoXyyKDx6WDm07x0hqbupoAoZB1qBP3_WfvWiJVAK4N1FD77EOYttUEHZ7L43xy5PCpojJQmkppGbPJc2jnTIc_F1vvGvw5fv8gBWZvPqTdb6LWF6FLrzwesZpi7j2rsioZ3wyUkqb5TDZaNNeWQrIEYXrEnWkRI_qTSOzx0dRTsGO6SpU1fPWuOOYMZG8Nh18nay0kLpxReuHCiCdxjXRVvk5k9rkcMbDBJcBovhiSioPKv_yJxcZVBATw3z3TTE95kGi4wxCEenxwhSpvouwa5b0hT7NS4Ay70QaxoKiLb3ZjhZaUUn4igCyZM0h6fllR5I6J_sAQxiMYD0v5ouIlb0u8YVMni93j3zlqMWdDUZ4WgTI7NNbo8ug9NQDHd92TPmSE1TytPTgya3tsFMzwyq0LZ0b-g-zSXWIES__jKQ7vAtIs9EwlPxqJXEDDniZ2AJ6biXRYgE2Kd6W_nmI7w31igwQTms3ecXe5ENI3ckEPUAq__llNnND7mxp5ZrdXzd5HHU9slXwDShYcW3yDeQLEwAVomTGSFpBrCX8W77n9hF3JClkWaeS4QcZ3xUtsSS81yLrp__ifFfQqx9_Be89WVyIOoF4oydr08EkZ8zwlAsbZLG7eLXY"
      },
      "callback":"\/post",
      "version":1,
      "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467"
    }

This packet says the following:

I'm Indigo and here is proof. I'm posting from podunk.edu and here is proof. I've got a package for you. The tracking number is "1eaa6613....". 

Example.com accepts this packet and says "whoa, hold on - I don't know you. I want to prove who you are." So Example.com connects to podunk.edu through a "well-known URL" that we use for this purpose and looks up the "guid" mentioned above. It should return a bunch of information, one item of which is a public key. Example.com uses this key to verify the signatures in the message to verify that indeed there is a person named Indigo at podunk.edu. We only need to do this once. (Note that Indigo can post from any location. All we have to do is prove that it's Indigo and that Indigo can prove that he's posting from another site.)

Then example.com disconnects and flags that there's a message waiting at podunk.edu. Either immediately, or whenever the urge hits (depending on how important Indigo is to anybody on this site), example.com "calls" podunk.edu. It says something like this:

    {
      "type":"pickup",
      "url":"http:\/\/example.com",
      "callback_sig":"teE1_fLIqfyeCuZY4iS7sNU8jUlUuqYOYBiHLarkC99I9K-uSr8DAwVW8ZPZRK-uYdxRMuKFb6cumF_Gt9XjecCPBM8HkoXHOi_VselzJkxPwor4ZPtWYWWaFtRfcAm794LrWjdz62zdESTQd2JJIZWbrli1sUhK801BF3n0Ye6-X1MWhy9EUTVlNimOeRipcuD_srMhUcAXOEbLlrugZ8ovy2YBe6YOXkS8jj0RSFjsOduXAoVhQmNpcobSYsDvaQS3e3MvE6-oXE602zGQhuNLr7DIMt9PCdAeQo-ZM-DHlZGCkGk4O2oQFCXFzGPqLUMWDACGJfTfIWGoh_EJqT_SD5b_Yi_Wk9S1lj7vb-lmxe5JuIf7ezWzHoBT8vswnZxPYlidH2i9wapdzij9il_qqcCWWHIp7q_XkY_Zj52Z4r4gdmiqM-8y1c_1SDX7hrJFRwqL_PKFbEvyi5nMWTEzqp55Tay5Woiv19STK_H_8ufFfD9AOkYnk6rIOMsk9dn3a5tAFpDRyRndXkBWAXwiJjiND2zjue7BFu7Ty40THXcfYRh1a5XrAXcaGeYuagg-8J9tAufu9_LY3qGazFg8kRBVMOn4M8DRKSIhKj7z4MnbYL0s09gREojy4jqWO3VkaOjP2jUGzoPuUDLasudE1ehWFq0K_MTQNavgmp8",
      "callback":"http:\/\/example.com\/post",
      "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467",
      "secret_sig":"O7nB4_UJHBXi28Suwl9LBZF9hI_9KGVTgehnUlWF1oYMNRnBbVHB9lzUfAoalvp3STbU3xJbtD_S58tv6MfV7J5j2V_S1W5ex3dulmDGB8Pt_7Fe5mbEPmjQFcfv3Eg5dUjYIuDl0TDScfrHyImj7RZIWHbwd7wWVoMzzDa_o33klpYmKZCBvObCh55bRrlFkXZs_dRuOiPwkfX0C6_XES4OyOIYl45V30rdhmf-STrf4L9dKYy_axQ12RIwRcKychvVLwlUJn3bn9lgNXRRU_HTne-09OPcJbUOdcD3DkFoKOxMULBNKPHzsCau0ICYug7S0EP6LpCom_mW78s08LyVA1vYeFZjevBCiGecj57yIAQDYi6_rpWJfihYaWHRN0oqtScUR4Bdf0bQbEHxMs4zAtrOAxfyJCbi6U1pfnGgzXzB9ulOYGnVGNTF7Ey4K7FOZIBtk0ILY2JfvBUaVvVs8ttagOOHmhWhnbCvrnOFlkNdlce7zoJCSUJENUOCYmTRfwB_Jno5fAzRnrsYU3_Z-l1mzniU_OmUPz8mPEh7PwhkqAiVlyaM-q15gn7l2lAIDk9kp2X_iCme7v4V0ADN_DbpaI_0-6mPw5HLbKrCsA-sxlSMB4DO4lDCHYkauj0l25sbfroRWB_hax1O4Q0oWyOlVJLUqEC5nuUJCCE"
    } 


What this message says is: This is example.com, I have proof, and I'm here to pick up a package. Here's the tracking number, and here's proof that this is the tracking number you presumably sent to example.com.

Good enough. Podunk.edu checks out the story and indeed, it is example.com, and yes, there's a package waiting with that tracking number. Here's the package...

    {
      "success":1,
      "pickup":{
        "notify":{
          "type":"notify",
          "sender":{
            "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
            "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q",
            "url":"http:\/\/z.podunk.edu",
            "url_sig":"T8Bp7j5DHHhQDCFcAHXfuhUfGk2P3inPbImwaXXF1xJd3TGgluoXyyKDx6WDm07x0hqbupoAoZB1qBP3_WfvWiJVAK4N1FD77EOYttUEHZ7L43xy5PCpojJQmkppGbPJc2jnTIc_F1vvGvw5fv8gBWZvPqTdb6LWF6FLrzwesZpi7j2rsioZ3wyUkqb5TDZaNNeWQrIEYXrEnWkRI_qTSOzx0dRTsGO6SpU1fPWuOOYMZG8Nh18nay0kLpxReuHCiCdxjXRVvk5k9rkcMbDBJcBovhiSioPKv_yJxcZVBATw3z3TTE95kGi4wxCEenxwhSpvouwa5b0hT7NS4Ay70QaxoKiLb3ZjhZaUUn4igCyZM0h6fllR5I6J_sAQxiMYD0v5ouIlb0u8YVMni93j3zlqMWdDUZ4WgTI7NNbo8ug9NQDHd92TPmSE1TytPTgya3tsFMzwyq0LZ0b-g-zSXWIES__jKQ7vAtIs9EwlPxqJXEDDniZ2AJ6biXRYgE2Kd6W_nmI7w31igwQTms3ecXe5ENI3ckEPUAq__llNnND7mxp5ZrdXzd5HHU9slXwDShYcW3yDeQLEwAVomTGSFpBrCX8W77n9hF3JClkWaeS4QcZ3xUtsSS81yLrp__ifFfQqx9_Be89WVyIOoF4oydr08EkZ8zwlAsbZLG7eLXY"
          },
          "callback":"\/post",
          "version":1,
          "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467"
        },
        "message":{
          "message_id":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
          "message_top":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
          "message_parent":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
          "created":"2012-11-20 04:04:16",
          "edited":"2012-11-20 04:04:16",
          "title":"",
          "body":"Hi Nickordo",
          "app":"",
          "verb":"post",
          "object_type":"",
          "target_type":"",
          "permalink":"",
          "location":"",
          "longlat":"",
          "owner":{
            "name":"Indigo",
            "address":"indigo@podunk.edu",
            "url":"http:\/\/podunk.edu",
            "photo":{
              "mimetype":"image\/jpeg",
              "src":"http:\/\/podunk.edu\/photo\/profile\/m\/5"
            },
            "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
            "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q"
          },
          "author":{
            "name":"Indigo",
            "address":"indigo@podunk.edu",
            "url":"http:\/\/podunk.edu",
            "photo":{
              "mimetype":"image\/jpeg",
              "src":"http:\/\/podunk.edu\/photo\/profile\/m\/5"
            },
            "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
            "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q"
          }
        }
      }
    }


 
And that's the package (the original message). Example.com converts this into a form suitable for viewing by Nickordo and notifies Nickordo that there's a new message. Podunk.edu **might** discover that there are other packages waiting for example.com. If this happens it may also send any and all other waiting packages at this time. Each has the original tracking number attached.  

#include doc/macros/main_footer.bb;
