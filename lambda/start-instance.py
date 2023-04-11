import boto3

ec2_client = boto3.client('ec2')

def lambda_handler(event, context):
    value = event["name"]
    F1={"Name":"tag:Name", "Values":[value]}
    ec2_response=ec2_client.describe_instances(Filters=[F1])
    for i in ec2_response["Reservations"]:
        for j in i['Instances']:
            id_responce=j['InstanceId']
            ec2_client.start_instances(InstanceIds=[id_responce])
    return "Instace will start wait for 1 mintues"