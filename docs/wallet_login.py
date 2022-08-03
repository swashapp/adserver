import requests
import os, sys
from web3.auto import w3
from eth_account.messages import encode_defunct

HOST = "https://app.swashapp.io"

def init():
   print("======init======")
   url = HOST + "/auth/login/wallet/init"
   response = requests.request("GET", url)
   print(response.text)
   return response.json()


def login(network, address, pk):
   r1 = init()
   token = r1['token']
   message = r1['message']
   url = HOST + "/auth/login/wallet"
   private_key = bytearray.fromhex(pk)
   message = encode_defunct(text=message)
   signed_message = w3.eth.account.sign_message(message, private_key=private_key)
   signature = signed_message.signature.hex()


   print("======login======")

   payload = {
    "token": token,
    "network": network,
    "address": address,
    "signature": signature
   }
   headers = {"Content-Type": "application/json"}

   response = requests.request("POST", url, json=payload, headers=headers)

   print(response.status_code)
   print(response.text)
   return response.json()

def me(network, address, pk):
  r = login(network, address, pk)
  print("authorization: Bearer " + r['apiToken'])

  print("======me======")
  url = HOST + "/auth/check"
  response = requests.request("GET", url, headers={"Content-Type": "application/json",
               'Authorization': 'Bearer {}'.format(r['apiToken'])})
  print(response.text)
  return response.json()

if __name__ == '__main__':
   try:
      network = os.environ["WL_NET"]
      address = os.environ["WL_ADR"]
      pk = os.environ["WL_PK"]
   except KeyError:
       print('Error: Provide network, address and private key.\nUsage: \n$ WL_NET=bsc WL_ADR=0xf123d WL_PK=9e123b5 python3 wallet_login.py')
       sys.exit(1)
   me(network, address, pk)